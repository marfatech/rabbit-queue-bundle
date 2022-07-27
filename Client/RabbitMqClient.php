<?php

declare(strict_types=1);

/*
 * This file is part of the RabbitQueueBundle package.
 *
 * (c) MarfaTech <https://marfa-tech.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MarfaTech\Bundle\RabbitQueueBundle\Client;

use ErrorException;
use Exception;
use InvalidArgumentException;
use MarfaTech\Bundle\RabbitQueueBundle\Enum\ExchangeEnum;
use MarfaTech\Bundle\RabbitQueueBundle\Enum\QueueHeaderOptionEnum;
use MarfaTech\Bundle\RabbitQueueBundle\Component\QueuePool;
use MarfaTech\Bundle\RabbitQueueBundle\Exception\QueuePoolNestedLevelException;
use MarfaTech\Bundle\RabbitQueueBundle\Exception\QueuePoolRollbackException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPHeartbeatMissedException;
use PhpAmqpLib\Exception\AMQPIOException;
use PhpAmqpLib\Exception\AMQPOutOfBoundsException;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Psr\Log\LoggerInterface;

class RabbitMqClient
{
    protected AMQPStreamConnection $connection;
    protected LoggerInterface $logger;
    protected ?AMQPChannel $channel = null;
    protected QueuePool $queuePool;

    public function __construct(
        AMQPStreamConnection $connection,
        LoggerInterface $logger,
        QueuePool $queuePool,
    ) {
        $this->connection = $connection;
        $this->logger = $logger;
        $this->queuePool = $queuePool;
    }

    public function isConsuming(): bool
    {
        return $this->getChannel()->is_consuming();
    }

    /**
     * @throws AMQPOutOfBoundsException
     * @throws AMQPRuntimeException
     * @throws AMQPTimeoutException
     * @throws ErrorException
     */
    public function wait(int $timeout = 0)
    {
        return $this->getChannel()->wait(null, false, $timeout);
    }

    /**
     * @throws AMQPTimeoutException
     * @throws InvalidArgumentException
     */
    public function consume(string $queueName, string $consumerName, callable $handler): string
    {
        return $this->getChannel()->basic_consume(
            $queueName,
            $consumerName,
            false,
            false,
            false,
            false,
            $handler
        );
    }

    /**
     * @throws AMQPTimeoutException
     */
    public function qos(int $batchSize)
    {
        return $this->getChannel()->basic_qos(null, $batchSize, null);
    }

    public function countNotTakenMessages(string $queueName)
    {
        [$queue, $messageCount, $consumerCount] = $this->getChannel()->queue_declare($queueName, true);

        return $messageCount;
    }

    /**
     * @param string $queueName
     * @param AMQPMessage[] $messageList
     * @param int $delay
     * @param array $deliveryTagContextList
     */
    public function rewindList(
        string $queueName,
        array $messageList,
        int $delay = 0,
        array $deliveryTagContextList = [],
    ): void {
        $this->ackList($messageList);

        foreach ($messageList as $message) {
            $deliveryTag = (string) $message->getDeliveryTag();
            $context = $deliveryTagContextList[$deliveryTag] ?? [];

            $headers = $message->has('application_headers') ? $message->get('application_headers') : new AMQPTable();

            $retryCount = $headers->getNativeData()[QueueHeaderOptionEnum::X_RETRY] ?? 0;

            $headers->set(QueueHeaderOptionEnum::X_DELAY, $delay * 1000);
            $headers->set(QueueHeaderOptionEnum::X_RETRY, ++$retryCount);
            $headers->set(QueueHeaderOptionEnum::X_CONTEXT, $context);

            $message->set('application_headers', $headers);

            $this->getChannel()->batch_basic_publish($message, ExchangeEnum::RETRY_EXCHANGE, $queueName);
        }

        $this->getChannel()->publish_batch();
    }

    /**
     * @param AMQPMessage[] $messageList
     */
    public function ackList(array $messageList): void
    {
        foreach ($messageList as $message) {
            $deliveryTag = $message->getDeliveryTag();

            $channel = $message->getChannel();
            $channel = $channel ?: $this->getChannel();

            $channel->basic_ack($deliveryTag);
        }
    }

    /**
     * @param AMQPMessage[] $messageList
     * @param bool $requeue
     */
    public function nackList(array $messageList, bool $requeue = true): void
    {
        foreach ($messageList as $message) {
            $deliveryTag = $message->getDeliveryTag();

            $channel = $message->getChannel();
            $channel = $channel ?: $this->getChannel();

            $channel->basic_nack($deliveryTag, false, $requeue);
        }
    }

    /**
     * @param AMQPMessage[] $messageList
     * @param bool $requeue
     */
    public function rejectList(array $messageList, bool $requeue = true): void
    {
        foreach ($messageList as $message) {
            $deliveryTag = $message->getDeliveryTag();

            $channel = $message->getChannel();
            $channel = $channel ?: $this->getChannel();

            $channel->basic_reject($deliveryTag, $requeue);
        }
    }

    /**
     * @throws Exception
     */
    public function __destruct()
    {
        if ($this->channel !== null) {
            $this->getChannel()->close();
            $this->connection->close();
        }
    }

    /**
     * @param AMQPMessage[] $messageList
     * @param string|null $exchangeName
     * @param string|null $queueName
     */
    public function publishBatch(array $messageList, string $exchangeName = null, string $queueName = null): void
    {
        if ($this->queuePool->getPoolNestingLevel() > 0) {
            foreach ($messageList as $message) {
                $this->queuePool->add($message, $exchangeName, $queueName);
            }
        } else {
            foreach ($messageList as $message) {
                $this->getChannel()->batch_basic_publish($message, $exchangeName, $queueName);
            }

            $this->getChannel()->publish_batch();
        }
    }

    /**
     * @throws AMQPIOException
     */
    public function publish(AMQPMessage $message, string $exchangeName = null, string $queueName = null): void
    {
        $this->checkConnection();

        if ($this->queuePool->getPoolNestingLevel() > 0) {
            $this->queuePool->add($message, $exchangeName, $queueName);
        } else {
            $this->getChannel()->basic_publish($message, $exchangeName, $queueName);
        }
    }

    public function beginPool(): void
    {
        $this->queuePool->incrementPoolNestingLevel();
    }

    /**
     * @throws AMQPIOException
     * @throws QueuePoolNestedLevelException
     * @throws QueuePoolRollbackException
     */
    public function commitPool(): void
    {
        if ($this->queuePool->isCommitAvailable()) {
            $this->flushPool();
        }

        $this->queuePool->decrementPoolNestingLevel();
    }

    /**
     * @throws QueuePoolNestedLevelException
     */
    public function rollbackPool(): void
    {
        $this->queuePool->rollbackPool();
    }

    /**
     * @throws AMQPIOException
     */
    protected function flushPool(): void
    {
        $messageList = $this->queuePool->getMessageList();

        if (empty($messageList)) {
            return;
        }

        $this->checkConnection();

        foreach ($messageList as $message) {
            $this->getChannel()->batch_basic_publish(
                $message->getAmqpMessage(),
                $message->getExchangeName(),
                $message->getQueueName()
            );
        }

        $this->getChannel()->publish_batch();

        $this->queuePool->clear();
    }

    protected function getChannel(): AMQPChannel
    {
        if ($this->channel === null) {
            $this->channel = $this->connection->channel();
        }

        return $this->channel;
    }

    /**
     * @throws AMQPIOException
     */
    protected function checkConnection(): void
    {
        try {
            $this->connection->checkHeartBeat();
        } catch (AMQPHeartbeatMissedException $e) {
            $this->logger->error('Missed server heartbeat: {exception}', [
                'exception' => (string) $e,
            ]);

            $this->connection->reconnect();
            $this->channel = $this->getChannel();
        }
    }
}
