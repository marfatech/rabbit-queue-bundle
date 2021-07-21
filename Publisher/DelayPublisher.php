<?php

declare(strict_types=1);

namespace MarfaTech\Bundle\RabbitQueueBundle\Publisher;

use MarfaTech\Bundle\RabbitQueueBundle\Definition\DefinitionInterface;
use MarfaTech\Bundle\RabbitQueueBundle\Enum\QueueHeaderOptionEnum;
use MarfaTech\Bundle\RabbitQueueBundle\Enum\QueueOptionEnum;
use MarfaTech\Bundle\RabbitQueueBundle\Enum\QueueTypeEnum;
use MarfaTech\Bundle\RabbitQueueBundle\Exception\RabbitQueueException;

use function is_int;
use function sprintf;

class DelayPublisher extends AbstractPublisher
{
    public const QUEUE_TYPE = QueueTypeEnum::FIFO | QueueTypeEnum::DELAY;

    protected function prepareOptions(DefinitionInterface $definition, array $options): array
    {
        $delay = $options[QueueOptionEnum::DELAY] ?? null;

        if (!is_int($delay)) {
            $message = sprintf(
                'Element for queue "%s" must be with option %s. See %s',
                $definition::getQueueName(),
                QueueOptionEnum::DELAY,
                QueueOptionEnum::class
            );

            throw new RabbitQueueException($message);
        }

        $amqpTableOption[QueueHeaderOptionEnum::X_DELAY] = $delay * 1000;

        return $amqpTableOption;
    }

    public static function getQueueType(): string
    {
        return (string) self::QUEUE_TYPE;
    }
}
