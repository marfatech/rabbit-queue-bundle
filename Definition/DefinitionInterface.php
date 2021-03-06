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

namespace MarfaTech\Bundle\RabbitQueueBundle\Definition;

use MarfaTech\Bundle\RabbitQueueBundle\Enum\QueueTypeEnum;
use PhpAmqpLib\Connection\AMQPStreamConnection;

interface DefinitionInterface
{
    public const TAG = 'marfatech_rabbit_queue.definition';

    /**
     * Declare definition to Rabbit MQ.
     * If definition is already exist, it will skip.
     */
    public function init(AMQPStreamConnection $connection);

    /**
     * Get queue name or exchange name which is an entry point for to handle message
     */
    public function getEntryPointName(): string;

    /**
     * Get queue type.
     * Allow combine types.
     * ex. QueueTypeEnum::FIFO | QueueTypeEnum::DEDUPLICATE | QueueTypeEnum::DELAY
     *
     * @see QueueTypeEnum
     */
    public function getQueueType(): int;

    /**
     * Queue name which is a storage for messages
     */
    public static function getQueueName(): string;
}
