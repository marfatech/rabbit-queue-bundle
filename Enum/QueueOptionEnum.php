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

namespace MarfaTech\Bundle\RabbitQueueBundle\Enum;

class QueueOptionEnum
{
    /**
     * Delay in seconds when message will be delivering to queue.
     */
    public const DELAY = 'delay';

    /**
     * Key for grouping messages.
     */
    public const KEY = 'key';
}
