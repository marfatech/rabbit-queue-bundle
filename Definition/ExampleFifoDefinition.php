<?php

declare(strict_types=1);

namespace MarfaTech\Bundle\RabbitQueueBundle\Definition;

use MarfaTech\Bundle\RabbitQueueBundle\Enum\QueueEnum;
use MarfaTech\Bundle\RabbitQueueBundle\Enum\QueueTypeEnum;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class ExampleFifoDefinition implements DefinitionInterface
{
    public const QUEUE_NAME = QueueEnum::EXAMPLE_FIFO;
    public const ENTRY_POINT = self::QUEUE_NAME;

    /**
     * {@inheritDoc}
     */
    public function init(AMQPStreamConnection $connection): void
    {
        $channel = $connection->channel();

        $channel->queue_declare(
            self::ENTRY_POINT,
            false,
            true,
            false,
            false
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getEntryPointName(): string
    {
        return self::ENTRY_POINT;
    }

    /**
     * {@inheritDoc}
     */
    public function getQueueType(): int
    {
        return QueueTypeEnum::FIFO;
    }

    /**
     * {@inheritDoc}
     */
    public static function getQueueName(): string
    {
        return self::QUEUE_NAME;
    }
}
