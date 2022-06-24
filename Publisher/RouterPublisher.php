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

class RouterPublisher extends AbstractPublisher
{
    public const QUEUE_TYPE = QueueTypeEnum::ROUTER;

    protected function prepareOptions(DefinitionInterface $definition, array $options): array
    {
        $amqpTableOption = [];

        if (isset($options[QueueOptionEnum::KEY])) {
            $amqpTableOption[QueueHeaderOptionEnum::X_DEDUPLICATION_HEADER] = $options[QueueOptionEnum::KEY];
        }

        if (isset($options[QueueOptionEnum::DELAY])) {
            $amqpTableOption[QueueHeaderOptionEnum::X_DELAY] = $options[QueueOptionEnum::DELAY] * 1000;
            $amqpTableOption[QueueHeaderOptionEnum::X_CACHE_TTL] = $options[QueueOptionEnum::DELAY] * 1000;
        }

        return array_merge($amqpTableOption, $options);
    }

    public static function getQueueType(): string
    {
        return (string) self::QUEUE_TYPE;
    }
}
