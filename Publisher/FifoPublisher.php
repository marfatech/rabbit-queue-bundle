<?php

declare(strict_types=1);

namespace MarfaTech\Bundle\RabbitQueueBundle\Publisher;

use MarfaTech\Bundle\RabbitQueueBundle\Definition\DefinitionInterface;
use MarfaTech\Bundle\RabbitQueueBundle\Enum\QueueTypeEnum;

class FifoPublisher extends AbstractPublisher
{
    public const QUEUE_TYPE = QueueTypeEnum::FIFO;

    protected function prepareOptions(DefinitionInterface $definition, array $options): array
    {
        return $options;
    }

    public static function getQueueType(): string
    {
        return (string) self::QUEUE_TYPE;
    }
}
