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

namespace MarfaTech\Bundle\RabbitQueueBundle\Consumer;

use MarfaTech\Bundle\RabbitQueueBundle\Enum\QueueEnum;

class ExampleFifoConsumer extends AbstractConsumer
{
    public const DEFAULT_BATCH_SIZE = 100;

    /**
     * {@inheritDoc}
     */
    public function process(array $messageList): void
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getBindQueueName(): string
    {
        return QueueEnum::EXAMPLE_FIFO;
    }

    /**
     * {@inheritDoc}
     */
    public static function getName(): string
    {
        return QueueEnum::EXAMPLE_FIFO;
    }
}
