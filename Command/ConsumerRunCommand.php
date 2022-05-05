<?php

declare(strict_types=1);

namespace MarfaTech\Bundle\RabbitQueueBundle\Command;

use Exception;
use JsonException;
use MarfaTech\Bundle\RabbitQueueBundle\Client\RabbitMqClient;
use MarfaTech\Bundle\RabbitQueueBundle\Consumer\ConsumerInterface;
use MarfaTech\Bundle\RabbitQueueBundle\Exception\ConsumerNotFoundException;
use MarfaTech\Bundle\RabbitQueueBundle\Exception\ConsumerSilentException;
use MarfaTech\Bundle\RabbitQueueBundle\Exception\DefinitionNotFoundException;
use MarfaTech\Bundle\RabbitQueueBundle\Exception\ReleasePartialException;
use MarfaTech\Bundle\RabbitQueueBundle\Exception\RewindDelayPartialException;
use MarfaTech\Bundle\RabbitQueueBundle\Exception\RewindPartialException;
use MarfaTech\Bundle\RabbitQueueBundle\Registry\ConsumerRegistry;
use MarfaTech\Bundle\RabbitQueueBundle\Registry\DefinitionRegistry;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

use function array_diff_key;
use function array_flip;
use function array_intersect_key;
use function array_map;
use function count;
use function explode;
use function in_array;
use function ini_get;
use function json_encode;
use function pcntl_signal;

use const JSON_THROW_ON_ERROR;

class ConsumerRunCommand extends Command
{
    protected static $defaultName = 'rabbit:consumer:run';

    private ConsumerRegistry $consumerRegistry;
    private RabbitMqClient $client;
    private DefinitionRegistry $definitionRegistry;
    private ParameterBagInterface $parameterBag;
    private ?LoggerInterface $logger;

    public function dependencyInjection(
        ConsumerRegistry $consumerRegistry,
        RabbitMqClient $client,
        DefinitionRegistry $definitionRegistry,
        ParameterBagInterface $parameterBag,
        ?LoggerInterface $logger = null
    ): void {
        $this->consumerRegistry = $consumerRegistry;
        $this->client = $client;
        $this->definitionRegistry = $definitionRegistry;
        $this->parameterBag = $parameterBag;
        $this->logger = $logger ?? new NullLogger();
    }

    public function stopConsumer(int $signal, $signalInfo): void
    {
        try {
            $signalInfo = json_encode($signalInfo, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $signalInfo = null;
        }

        $this->logger->info('Consumer has been stopped forcibly with signal: {signal}. Context: {context}', [
            'signal' => $signal,
            'context' => $signalInfo,
        ]);

        exit();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Run consumer')
            ->setHelp('This command allows you to run any consumer by his name')
            ->addArgument('name', InputArgument::REQUIRED, 'Name of the consumer')
        ;
    }

    /**
     * {@inheritdoc}
     *
     * @throws ConsumerNotFoundException
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        declare(ticks=1);

        $disableFunctionList = explode(',', ini_get('disable_functions'));
        $disableFunctionList = array_map('trim', $disableFunctionList);

        if (in_array('pcntl_signal', $disableFunctionList, true)) {
            throw new RuntimeException('error occurred: function `pcntl_signal` disabled');
        }

        pcntl_signal(SIGTERM, [$this, 'stopConsumer']);
        pcntl_signal(SIGINT, [$this, 'stopConsumer']);
        pcntl_signal(SIGHUP, [$this, 'stopConsumer']);

        $name = $input->getArgument('name');
        $consumer = $this->consumerRegistry->getConsumer($name);
        $queueName = $consumer->getBindQueueName();
        $batchSize = $consumer->getBatchSize();

        $messageList = [];

        $this->client->qos($batchSize);
        $this->client->consume($queueName, $name, function (AMQPMessage $message) use (&$messageList) {
            $messageList[$message->getDeliveryTag()] = $message;
        });

        $batchTime = 0;

        while ($this->client->isConsuming()) {
            if (count($messageList) === $batchSize || $batchTime >= $this->getBatchTimeout()) {
                $this->batchConsume($consumer, $messageList);
                $batchTime = 0;
            }

            $timeout = empty($messageList) ? $this->getIdleTimeout() : $this->getWaitTimeout();
            $timeStart = microtime(true);

            try {
                $this->client->wait($timeout);
                $batchTime += microtime(true) - $timeStart;
            } catch (AMQPTimeoutException $e) {
                if (!empty($messageList)) {
                    $this->batchConsume($consumer, $messageList);
                }
            }
        }

        return 0;
    }

    /**
     * @throws DefinitionNotFoundException
     */
    protected function batchConsume(ConsumerInterface $consumer, array &$messageList): void
    {
        try {
            $consumer->process($messageList);
            $this->client->ackList($messageList);

            $consumer->incrementProcessedTasksCounter();

            $maxProcessedTasksCount = $consumer->getMaxProcessedTasksCount();

            if ($maxProcessedTasksCount > 0 && $maxProcessedTasksCount <= $consumer->getProcessedTasksCounter()) {
                $consumer->stopPropagation();
            }
        } catch (RewindPartialException $exception) {
            $rewindDeliveryTagList = $exception->getRewindDeliveryTagList();

            [$rewindMessageList, $ackMessageList] = $this->getPartialMessageList($messageList, $rewindDeliveryTagList);

            $this->client->rewindList($consumer->getBindQueueName(), $rewindMessageList);
            $this->client->ackList($ackMessageList);
        } catch (RewindDelayPartialException $exception) {
            $definition = $this->definitionRegistry->getDefinition($consumer->getBindQueueName());
            $rewindDeliveryTagList = $exception->getRewindDeliveryTagList();

            [$rewindMessageList, $ackMessageList] = $this->getPartialMessageList($messageList, $rewindDeliveryTagList);

            $this->client->rewindList($definition::getQueueName(), $rewindMessageList, $exception->getDelay());
            $this->client->ackList($ackMessageList);
        } catch (ConsumerSilentException $exception) {
            $this->client->nackList($messageList);
        } catch (ReleasePartialException $exception) {
            $releaseDeliveryTagList = $exception->getReleaseDeliveryTagList();

            [$releaseMessageList, $ackMessageList] = $this->getPartialMessageList(
                $messageList,
                $releaseDeliveryTagList
            );

            $this->client->nackList($releaseMessageList);
            $this->client->ackList($ackMessageList);
        } catch (Exception $exception) {
            $this->logger->warning('Error process queue: {exception}', [
                'exception' => $exception,
            ]);

            $this->client->nackList($messageList);

            throw $exception;
        } finally {
            $messageList = [];

            if ($consumer->isPropagationStopped()) {
                $this->logger->info('Consumer has been propagation stopped forcibly');

                exit(0);
            }
        }
    }

    /**
     * @param AMQPMessage[] $messageList
     * @param int[] $deliveryTagList
     *
     * @return array<int, array<int, AMQPMessage>>
     */
    private function getPartialMessageList(array $messageList, array $deliveryTagList): array
    {
        $deliveryTagList = array_flip($deliveryTagList);

        $intersectMessageList = array_intersect_key($messageList, $deliveryTagList);
        $diffMessageList = array_diff_key($messageList, $deliveryTagList);

        return [$intersectMessageList, $diffMessageList];
    }

    private function getIdleTimeout(): int
    {
        return $this->parameterBag->get('marfatech_rabbit_queue.consumer.idle_timeout');
    }

    private function getWaitTimeout(): int
    {
        return $this->parameterBag->get('marfatech_rabbit_queue.consumer.wait_timeout');
    }

    private function getBatchTimeout(): int
    {
        return $this->parameterBag->get('marfatech_rabbit_queue.consumer.batch_timeout');
    }
}
