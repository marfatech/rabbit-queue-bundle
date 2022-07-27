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

namespace MarfaTech\Bundle\RabbitQueueBundle\Dto;

use PhpAmqpLib\Message\AMQPMessage;

class QueuePoolMessage
{
    public function __construct(
        private AMQPMessage $amqpMessage,
        private string $exchangeName,
        private string $queueName,
    ) {
    }

    public function getExchangeName(): string
    {
        return $this->exchangeName;
    }

    public function getQueueName(): string
    {
        return $this->queueName;
    }

    public function getAmqpMessage(): AMQPMessage
    {
        return $this->amqpMessage;
    }
}
