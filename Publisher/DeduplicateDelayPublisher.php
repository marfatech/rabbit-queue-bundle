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

namespace MarfaTech\Bundle\RabbitQueueBundle\Publisher;

use MarfaTech\Bundle\RabbitQueueBundle\Definition\DefinitionInterface;
use MarfaTech\Bundle\RabbitQueueBundle\Enum\QueueHeaderOptionEnum;
use MarfaTech\Bundle\RabbitQueueBundle\Enum\QueueOptionEnum;
use MarfaTech\Bundle\RabbitQueueBundle\Enum\QueueTypeEnum;
use MarfaTech\Bundle\RabbitQueueBundle\Exception\RabbitQueueException;

use function is_int;
use function is_string;
use function sprintf;

class DeduplicateDelayPublisher extends AbstractPublisher
{
    public const QUEUE_TYPE = QueueTypeEnum::FIFO | QueueTypeEnum::DELAY | QueueTypeEnum::DEDUPLICATE;

    /**
     * @throws RabbitQueueException
     */
    protected function prepareOptions(DefinitionInterface $definition, array $options): array
    {
        $key = $options[QueueOptionEnum::KEY] ?? null;
        $delay = $options[QueueOptionEnum::DELAY] ?? null;

        if (!is_string($key) || !is_int($delay)) {
            $message = sprintf(
                'Element for queue "%s" must be with options %s/%s. See %s',
                $definition::getQueueName(),
                QueueOptionEnum::KEY,
                QueueOptionEnum::DELAY,
                QueueOptionEnum::class
            );
            throw new RabbitQueueException($message);
        }

        $amqpTableOption[QueueHeaderOptionEnum::X_DEDUPLICATION_HEADER] = $key;
        $amqpTableOption[QueueHeaderOptionEnum::X_DELAY] = $delay * 1000;
        $amqpTableOption[QueueHeaderOptionEnum::X_CACHE_TTL] = $delay * 1000;
        
        return $amqpTableOption;
    }

    public static function getQueueType(): string
    {
        return (string) self::QUEUE_TYPE;
    }
}
