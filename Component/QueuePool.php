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

namespace MarfaTech\Bundle\RabbitQueueBundle\Component;

use MarfaTech\Bundle\RabbitQueueBundle\Dto\QueuePoolMessage;
use MarfaTech\Bundle\RabbitQueueBundle\Exception\QueuePoolNestedLevelException;
use PhpAmqpLib\Message\AMQPMessage;
use MarfaTech\Bundle\RabbitQueueBundle\Exception\QueuePoolRollbackException;

class QueuePool
{
    protected array $messageList = [];
    protected int $poolNestedLevel = 0;
    protected bool $isRollback = false;

    /**
     * @return QueuePoolMessage[]
     */
    public function getMessageList(): array
    {
        return $this->messageList;
    }

    public function add(AMQPMessage $message, string $exchangeName, string $queueName): void
    {
        $this->messageList[] = new QueuePoolMessage($message, $exchangeName, $queueName);
    }

    public function clear(): void
    {
        $this->messageList = [];
    }

    public function incrementPoolNestingLevel(): void
    {
        $this->poolNestedLevel++;
    }

    public function decrementPoolNestingLevel(): void
    {
        $this->poolNestedLevel--;
    }

    /**
     * @throws QueuePoolNestedLevelException
     */
    public function rollbackPool(): void
    {
        $isRollbackAvailable = $this->isRollbackAvailable();

        if ($isRollbackAvailable) {
            $this->resetPoolNestingLevel();
            $this->clear();
            $this->setRollback(false);
        } else {
            $this->decrementPoolNestingLevel();
            $this->setRollback(true);
        }
    }

    public function setRollback(bool $value): void
    {
        $this->isRollback = $value;
    }

    /**
     * @throws QueuePoolNestedLevelException
     * @throws QueuePoolRollbackException
     */
    public function isCommitAvailable(): bool
    {
        $this->checkPoolNestedLevel();
        $this->checkRollback();

        return $this->poolNestedLevel === 1;
    }

    /**
     * @throws QueuePoolNestedLevelException
     */
    public function isRollbackAvailable(): bool
    {
        $this->checkPoolNestedLevel();

        return $this->poolNestedLevel === 1;
    }

    public function resetPoolNestingLevel(): void
    {
        $this->poolNestedLevel = 0;
    }

    public function getPoolNestingLevel(): int
    {
        return $this->poolNestedLevel;
    }

    /**
     * @throws QueuePoolRollbackException
     */
    protected function checkRollback(): void
    {
        if ($this->isRollback === true) {
            throw new QueuePoolRollbackException('Queue pool has been marked for rollback only');
        }
    }

    /**
     * @throws QueuePoolNestedLevelException
     */
    protected function checkPoolNestedLevel(): void
    {
        if ($this->poolNestedLevel === 0) {
            throw new QueuePoolNestedLevelException('Incorrect queue pool nested level');
        }
    }
}
