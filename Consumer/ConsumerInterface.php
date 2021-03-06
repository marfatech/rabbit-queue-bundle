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

use PhpAmqpLib\Message\AMQPMessage;

interface ConsumerInterface
{
    public const TAG = 'marfatech_rabbit_queue.consumer';

    public function getBatchSize(): int;

    public function isPropagationStopped(): bool;

    public function stopPropagation(): void;

    public function incrementProcessedTasksCounter(): void;

    public function getProcessedTasksCounter(): int;

    public function getMaxProcessedTasksCount(): int;

    /**
     * Handle messages
     *
     * @param AMQPMessage[] $messageList
     */
    public function process(array $messageList);

    /**
     * Get queue name which have bind with this consumer.
     */
    public function getBindQueueName(): string;

    /**
     * Get consumer name.
     * It will be needed when you will be running the consumer.
     */
    public static function getName(): string;
}
