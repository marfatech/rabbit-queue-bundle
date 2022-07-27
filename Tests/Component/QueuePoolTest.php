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

namespace MarfaTech\Bundle\RabbitQueueBundle\Tests\Component;

use MarfaTech\Bundle\RabbitQueueBundle\Component\QueuePool;
use MarfaTech\Bundle\RabbitQueueBundle\Exception\QueuePoolNestedLevelException;
use MarfaTech\Bundle\RabbitQueueBundle\Exception\QueuePoolRollbackException;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\TestCase;

class QueuePoolTest extends TestCase
{
    private const EXCHANGE_NAME = 'test_exchange';
    private const ROUTING_KEY = 'test_routing_key';

    public function testAddAndGetMessageList(): void
    {
        $messageMock = $this->createMock(AMQPMessage::class);
        $queuePool = new QueuePool();

        $queuePool->add($messageMock, self::EXCHANGE_NAME, self::ROUTING_KEY);

        $addedMessage = current($queuePool->getMessageList());

        self::assertSame(
            [$messageMock, self::EXCHANGE_NAME, self::ROUTING_KEY],
            [$addedMessage->getAmqpMessage(), $addedMessage->getExchangeName(), $addedMessage->getQueueName()]
        );
    }

    public function testClear(): void
    {
        $messageMock = $this->createMock(AMQPMessage::class);
        $queuePool = new QueuePool();

        $queuePool->add($messageMock, self::EXCHANGE_NAME, self::ROUTING_KEY);
        $queuePool->clear();

        self::assertSame([], $queuePool->getMessageList());
    }

    public function testIncrementPoolNestedLevel(): void
    {
        $queuePool = new QueuePool();

        $queuePool->incrementPoolNestingLevel();

        self::assertSame(1, $queuePool->getPoolNestingLevel());
    }

    public function testDecrementPoolNestedLevel(): void
    {
        $queuePool = new QueuePool();

        $queuePool->decrementPoolNestingLevel();

        self::assertSame(-1, $queuePool->getPoolNestingLevel());
    }

    public function testIsCommitAvailableIncorrectNestedLevel(): void
    {
        $queuePool = new QueuePool();

        $this->expectException(QueuePoolNestedLevelException::class);

        $queuePool->isCommitAvailable();
    }

    /**
     * @throws QueuePoolRollbackException
     * @throws QueuePoolNestedLevelException
     */
    public function testIsCommitAvailable(): void
    {
        $queuePool = new QueuePool();
        $queuePool->incrementPoolNestingLevel();

        self::assertTrue($queuePool->isCommitAvailable());
    }

    /**
     * @throws QueuePoolNestedLevelException
     */
    public function testIsRollbackAvailable(): void
    {
        $queuePool = new QueuePool();
        $queuePool->incrementPoolNestingLevel();

        self::assertTrue($queuePool->isRollbackAvailable());
    }

    public function testIsRollbackAvailableIncorrectNestedLevel(): void
    {
        $queuePool = new QueuePool();

        $this->expectException(QueuePoolNestedLevelException::class);

        $queuePool->isRollbackAvailable();
    }

    public function testResetPoolNestedLevel(): void
    {
        $queuePool = new QueuePool();
        $queuePool->incrementPoolNestingLevel();

        $queuePool->resetPoolNestingLevel();

        self::assertSame(0, $queuePool->getPoolNestingLevel());
    }

    /**
     * @throws QueuePoolNestedLevelException
     */
    public function testRollbackPool(): void
    {
        $queuePool = new QueuePool();
        $queuePool->incrementPoolNestingLevel();

        $queuePool->rollbackPool();

        self::assertSame(0, $queuePool->getPoolNestingLevel());
        self::assertSame([], $queuePool->getMessageList());
    }

    /**
     * @throws QueuePoolNestedLevelException
     */
    public function testRollbackPoolWithNestedLevel(): void
    {
        $queuePool = new QueuePool();
        $queuePool->incrementPoolNestingLevel();
        $queuePool->incrementPoolNestingLevel();

        $queuePool->rollbackPool();

        self::assertSame(1, $queuePool->getPoolNestingLevel());
    }

    /**
     * @throws QueuePoolNestedLevelException
     * @throws QueuePoolRollbackException
     */
    public function testRollbackPoolWithNestedLevelCommitException(): void
    {
        $this->expectException(QueuePoolRollbackException::class);

        $queuePool = new QueuePool();
        $queuePool->incrementPoolNestingLevel();
        $queuePool->incrementPoolNestingLevel();

        $queuePool->rollbackPool();
        $queuePool->isCommitAvailable();
    }
}
