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

namespace MarfaTech\Bundle\RabbitQueueBundle\Tests\Client;

use MarfaTech\Bundle\RabbitQueueBundle\Client\RabbitMqClient;
use MarfaTech\Bundle\RabbitQueueBundle\Exception\QueuePoolNestedLevelException;
use MarfaTech\Bundle\RabbitQueueBundle\Component\QueuePool;
use MarfaTech\Bundle\RabbitQueueBundle\Exception\QueuePoolRollbackException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPIOException;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class RabbitMqClientTest extends TestCase
{
    private const EXCHANGE_NAME = 'test_exchange';
    private const ROUTING_KEY = 'test_routing_key';

    /**
     * @throws QueuePoolRollbackException
     * @throws QueuePoolNestedLevelException
     * @throws AMQPIOException
     */
    public function testOneTransaction(): void
    {
        $channelMock = $this->createMock(AMQPChannel::class);
        $connection = $this->createMock(AMQPStreamConnection::class);
        $logger = $this->createMock(LoggerInterface::class);
        $messageMock = $this->createMock(AMQPMessage::class);

        $connection
            ->method('channel')
            ->willReturn($channelMock)
        ;
        $channelMock
            ->expects(self::never())
            ->method('basic_publish')
            ->willReturn($channelMock)
        ;
        $channelMock
            ->expects(self::once())
            ->method('batch_basic_publish')
        ;
        $channelMock
            ->expects(self::once())
            ->method('publish_batch')
        ;

        $queuePool = $this
            ->getMockBuilder(QueuePool::class)
            ->onlyMethods(['add', 'getMessageList'])
            ->getMock()
        ;
        $queuePool
            ->expects(self::once())
            ->method('getMessageList')
            ->willReturn([[$messageMock, self::EXCHANGE_NAME, self::ROUTING_KEY]])
        ;
        $queuePool
            ->expects(self::once())
            ->method('add')
            ->with($messageMock, self::EXCHANGE_NAME, self::ROUTING_KEY)
        ;

        $client = new RabbitMqClient($connection, $logger, $queuePool);

        $client->beginPool();
        $client->publish($messageMock, self::EXCHANGE_NAME, self::ROUTING_KEY);
        $client->commitPool();
    }

    /**
     * @throws QueuePoolRollbackException
     * @throws QueuePoolNestedLevelException
     * @throws AMQPIOException
     */
    public function testMultipleInternalTransaction(): void
    {
        $channelMock = $this->createMock(AMQPChannel::class);
        $connection = $this->createMock(AMQPStreamConnection::class);
        $connection
            ->method('channel')
            ->willReturn($channelMock)
        ;
        $channelMock
            ->expects(self::never())
            ->method('basic_publish')
            ->willReturn($channelMock)
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $messageMock = $this->createMock(AMQPMessage::class);

        $queuePool = new QueuePool();
        $client = new RabbitMqClient($connection, $logger, $queuePool);

        $client->beginPool();
        self::assertSame(1, $queuePool->getPoolNestingLevel());

        $client->publish($messageMock, self::EXCHANGE_NAME, self::ROUTING_KEY);

        $client->beginPool();
        self::assertSame(2, $queuePool->getPoolNestingLevel());

        $client->publish($messageMock, self::EXCHANGE_NAME, self::ROUTING_KEY);

        $client->commitPool();
        self::assertSame(1, $queuePool->getPoolNestingLevel());

        $client->commitPool();
        self::assertSame(0, $queuePool->getPoolNestingLevel());
    }

    /**
     * @throws QueuePoolRollbackException
     * @throws AMQPIOException
     */
    public function testIncorrectTransactionNestedLevel(): void
    {
        $connection = $this->createMock(AMQPStreamConnection::class);
        $logger = $this->createMock(LoggerInterface::class);

        $queuePool = new QueuePool();
        $client = new RabbitMqClient($connection, $logger, $queuePool);

        $this->expectException(QueuePoolNestedLevelException::class);

        $client->beginPool();
        $client->commitPool();
        $client->commitPool();
    }

    /**
     * @throws QueuePoolNestedLevelException
     * @throws QueuePoolRollbackException
     * @throws AMQPIOException
     */
    public function testUnclosedPoolRollback(): void
    {
        $channelMock = $this->createMock(AMQPChannel::class);
        $connection = $this->createMock(AMQPStreamConnection::class);
        $connection
            ->method('channel')
            ->willReturn($channelMock)
        ;
        $channelMock
            ->expects(self::never())
            ->method('basic_publish')
            ->willReturn($channelMock)
        ;
        $channelMock
            ->expects(self::never())
            ->method('publish_batch')
            ->willReturn($channelMock)
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $messageMock = $this->createMock(AMQPMessage::class);

        $queuePool = new QueuePool();
        $client = new RabbitMqClient($connection, $logger, $queuePool);

        $client->beginPool();
        $client->beginPool();
        $client->publish($messageMock, self::EXCHANGE_NAME, self::ROUTING_KEY);
        $client->commitPool();
    }
}
