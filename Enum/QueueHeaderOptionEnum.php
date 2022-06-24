<?php

declare(strict_types=1);

namespace MarfaTech\Bundle\RabbitQueueBundle\Enum;

class QueueHeaderOptionEnum
{
    public const X_DELAY = 'x-delay';
    public const X_RETRY = 'x-retry';
    public const X_DEDUPLICATION_HEADER = 'x-deduplication-header';
    public const X_CACHE_TTL = 'x-cache-ttl';
    public const X_CONTEXT = 'x-context';
}
