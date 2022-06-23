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
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPOutOfBoundsException;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class RabbitMqClient
{
    private AMQPStreamConnection $connection;
    private AMQPChannel $channel;

    public function __construct(
        AMQPStreamConnection $connection
    ) {
        $this->connection = $connection;
        $this->channel = $connection->channel();
    }

    public function isConsuming(): bool
    {
        return $this->channel->is_consuming();
    }

    /**
     * @throws AMQPOutOfBoundsException
     * @throws AMQPRuntimeException
     * @throws AMQPTimeoutException
     * @throws ErrorException
     */
    public function wait(int $timeout = 0)
    {
        return $this->channel->wait(null, false, $timeout);
    }

    /**
     * @throws AMQPTimeoutException
     * @throws InvalidArgumentException
     */
    public function consume(string $queueName, string $consumerName, callable $handler): string
    {
        return $this->channel->basic_consume(
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
        return $this->channel->basic_qos(null, $batchSize, null);
    }

    public function countNotTakenMessages(string $queueName)
    {
        [$queue, $messageCount, $consumerCount] = $this->channel->queue_declare($queueName, true);

        return $messageCount;
    }

    /**
     * @param string $queueName
     * @param AMQPMessage[] $messageList
     * @param int $delay
     */
    public function rewindList(
        string $queueName,
        array $messageList,
        int $delay = 0
    ): void {
        $this->ackList($messageList);

        foreach ($messageList as $message) {
            $headers = $message->has('application_headers') ? $message->get('application_headers') : new AMQPTable();

            $retryCount = $headers->getNativeData()[QueueHeaderOptionEnum::X_RETRY] ?? 0;

            $headers->set(QueueHeaderOptionEnum::X_DELAY, $delay * 1000);
            $headers->set(QueueHeaderOptionEnum::X_RETRY, ++$retryCount);

            $message->set('application_headers', $headers);

            $this->channel->batch_basic_publish($message, ExchangeEnum::RETRY_EXCHANGE, $queueName);
        }

        $this->channel->publish_batch();
    }

    /**
     * @param AMQPMessage[] $messageList
     */
    public function ackList(array $messageList): void
    {
        foreach ($messageList as $message) {
            $deliveryTag = $message->getDeliveryTag();

            $channel = $message->getChannel();
            $channel = $channel ?: $this->channel;

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
            $channel = $channel ?: $this->channel;

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
            $channel = $channel ?: $this->channel;

            $channel->basic_reject($deliveryTag, $requeue);
        }
    }

    /**
     * @throws Exception
     */
    public function __destruct()
    {
        $this->channel->close();
        $this->connection->close();
    }

    /**
     * @param AMQPMessage[] $messageList
     * @param string|null $exchangeName
     * @param string|null $queueName
     */
    public function publishBatch(array $messageList, string $exchangeName = null, string $queueName = null): void
    {
        foreach ($messageList as $message) {
            $this->channel->batch_basic_publish($message, $exchangeName, $queueName);
        }

        $this->channel->publish_batch();
    }

    public function publish(AMQPMessage $message, string $exchangeName = null, string $queueName = null): void
    {
        $this->channel->basic_publish($message, $exchangeName, $queueName);
    }
}
