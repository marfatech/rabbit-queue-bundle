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
        $mockExampleConsumer = $this->createMock(ExampleConsumer::class);
        $mockExampleConsumer
            ->method('process')
            ->will(
                $this->throwException(
                    new RewindPartialException(
                        [],
                        [
                            'test' => 'succesfull',
                        ],
                    )
                )
            )
        ;

        $mockConsumerRegistry = $this->createMock(ConsumerRegistry::class);
        $mockConsumerRegistry
            ->method('getConsumer')
            ->willReturn($mockExampleConsumer)
        ;

        $mockChannel = $this->createMock(AMQPChannel::class);
        $mockChannel
            ->method('basic_consume')
            ->willReturn('')
        ;
        $mockChannel
            ->method('is_consuming')
            ->willReturn(true)
        ;
        $mockChannel
            ->method('publish_batch')
            ->will(
            $this->throwException(new RabbitQueueException())
        )
        ;

        ob_start();
        $mockConnection = $this->createMock(AMQPStreamConnection::class);
        $mockConnection
            ->method('channel')
            ->willReturn($mockChannel)
        ;
        ob_end_clean();


        $mockRabbitClient = $this->getMockBuilder(RabbitMqClient::class)
            ->setConstructorArgs([$mockConnection])
            ->enableOriginalConstructor()
            ->enableProxyingToOriginalMethods()
            ->getMock()
        ;

        $mockRabbitClient
            ->expects(self::once())
            ->method('rewindList')
            ->withConsecutive(
                [
                    '',
                    [],
                    0,
                    [
                        'test' => 'succesfull',
                    ],
                ]
            )
        ;

        $command = new ConsumerRunCommand();

        $command->dependencyInjection(
            $mockConsumerRegistry,
            $mockRabbitClient,
            $this->createMock(DefinitionRegistry::class),
            $this->createMock(ParameterBagInterface::class)
        );

        $this->expectException(RabbitQueueException::class);
        $commandTester = new CommandTester($command);
        $commandTester->execute(['name' => 'example']);
    }
}
