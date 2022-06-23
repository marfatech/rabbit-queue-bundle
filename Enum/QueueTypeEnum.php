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

class QueueTypeEnum
{
    public const FIFO = 1;
    public const DELAY = 2;
    public const REPLACE = 4;
    public const DEDUPLICATE = 8;
    public const ROUTER = 16;
}
