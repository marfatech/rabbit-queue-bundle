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

namespace MarfaTech\Bundle\RabbitQueueBundle\Tests\Command;

use MarfaTech\Bundle\RabbitQueueBundle\Client\RabbitMqClient;
use MarfaTech\Bundle\RabbitQueueBundle\Command\ConsumerRunCommand;
use MarfaTech\Bundle\RabbitQueueBundle\Consumer\ExampleConsumer;
use MarfaTech\Bundle\RabbitQueueBundle\Exception\RabbitQueueException;
use MarfaTech\Bundle\RabbitQueueBundle\Exception\RewindPartialException;
use MarfaTech\Bundle\RabbitQueueBundle\Registry\ConsumerRegistry;
use MarfaTech\Bundle\RabbitQueueBundle\Registry\DefinitionRegistry;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

use function ob_start;
use function ob_end_clean;

class ConsumerRunCommandTest extends TestCase
{
    private Application $application;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->application = new Application();

        $command = new ConsumerRunCommand();
        $command->dependencyInjection(
            $this->createMock(ConsumerRegistry::class),
            $this->createMock(RabbitMqClient::class),
            $this->createMock(DefinitionRegistry::class),
            $this->createMock(ParameterBagInterface::class)
        );

        $this->application->add($command);
    }

    public function testExecute(): void
    {
        $command = $this->application->find('rabbit:consumer:run');
        $commandTester = new CommandTester($command);

        $commandTester->execute(['name' => 'example']);

        $statusCode = $commandTester->getStatusCode();

        self::assertSame(0, $statusCode, $commandTester->getDisplay());
    }

    public function testExecuteFailWithoutNameParameter(): void
    {
        $this->expectException(RuntimeException::class);

        $command = $this->application->find('rabbit:consumer:run');
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);
    }

    public function testExecuteWithClientRewindListWithMessageTagTitle(): void
    {
        $exampleConsumerMock = $this->createMock(ExampleConsumer::class);
        $exampleConsumerMock
            ->method('process')
            ->will(
                $this->throwException(
                    new RewindPartialException(
                        [],
                        [
                            'test' => 'successful',
                        ],
                    )
                )
            )
        ;

        $consumerRegistryMock = $this->createMock(ConsumerRegistry::class);
        $consumerRegistryMock
            ->method('getConsumer')
            ->willReturn($exampleConsumerMock)
        ;

        $channelMock = $this->createMock(AMQPChannel::class);
        $channelMock
            ->method('basic_consume')
            ->willReturn('')
        ;
        $channelMock
            ->method('is_consuming')
            ->willReturn(true)
        ;
        $channelMock
            ->method('publish_batch')
            ->will($this->throwException(new RabbitQueueException()))
        ;

        ob_start();
        $connectionMock = $this->createMock(AMQPStreamConnection::class);
        $connectionMock
            ->method('channel')
            ->willReturn($channelMock)
        ;
        ob_end_clean();


        $rabbitClientMock = $this->getMockBuilder(RabbitMqClient::class)
            ->setConstructorArgs([$connectionMock])
            ->enableOriginalConstructor()
            ->enableProxyingToOriginalMethods()
            ->getMock()
        ;

        $rabbitClientMock
            ->expects(self::once())
            ->method('rewindList')
            ->withConsecutive(
                [
                    '',
                    [],
                    0,
                    [
                        'test' => 'successful',
                    ],
                ]
            )
        ;

        $command = new ConsumerRunCommand();

        $command->dependencyInjection(
            $consumerRegistryMock,
            $rabbitClientMock,
            $this->createMock(DefinitionRegistry::class),
            $this->createMock(ParameterBagInterface::class)
        );

        $this->expectException(RabbitQueueException::class);
        $commandTester = new CommandTester($command);
        $commandTester->execute(['name' => 'example']);
    }
}
