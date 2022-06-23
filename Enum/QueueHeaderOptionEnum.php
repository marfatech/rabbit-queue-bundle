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

class QueueHeaderOptionEnum
{
    public const X_DELAY = 'x-delay';
    public const X_RETRY = 'x-retry';
    public const X_DEDUPLICATION_HEADER = 'x-deduplication-header';
    public const X_CACHE_TTL = 'x-cache-ttl';
}
