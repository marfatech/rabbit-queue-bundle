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
use MarfaTech\Bundle\RabbitQueueBundle\Enum\ExchangeEnum;
use MarfaTech\Bundle\RabbitQueueBundle\Enum\QueueHeaderOptionEnum;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use PHPUnit\Framework\TestCase;

use function ob_end_clean;
use function ob_start;

class RabbitClientTest extends TestCase
{
    public function testClientRewindMessage(): void
    {
        ob_start();
        $queueName = 'test_queue';
        $deliveryTag = 3;
        $rewindReason = ['abc'];

        $headers = new AMQPTable();
        $headers->set(QueueHeaderOptionEnum::X_DELAY, 0);
        $headers->set(QueueHeaderOptionEnum::X_RETRY, 1);
        $headers->set(QueueHeaderOptionEnum::X_CONTEXT, $rewindReason);

        $expectedMessage = new AMQPMessage();
        $expectedMessage->setDeliveryInfo($deliveryTag, '', '', '');
        $expectedMessage->set('application_headers', $headers);

        $channelMock = $this->createMock(AMQPChannel::class);
        $channelMock
            ->expects(self::once())
            ->method('batch_basic_publish')
            ->withConsecutive(
                [
                    $expectedMessage,
                    ExchangeEnum::RETRY_EXCHANGE,
                    $queueName,
                ]
            )
        ;

        $connectionMock = $this->createMock(AMQPStreamConnection::class);
        $connectionMock
            ->method('channel')
            ->willReturn($channelMock)
        ;

        $rabbitClientMock = $this->getMockBuilder(RabbitMqClient::class)
            ->setConstructorArgs([$connectionMock])
            ->enableOriginalConstructor()
            ->enableProxyingToOriginalMethods()
            ->getMock()
        ;

        $message = new AMQPMessage();
        $message->setDeliveryInfo($deliveryTag, '', '', '');

        $rabbitClientMock->rewindList($queueName, [$message], deliveryTagContextList: [$deliveryTag => $rewindReason]);
        ob_end_clean();
    }
}
