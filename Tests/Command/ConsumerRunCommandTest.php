<?php

declare(strict_types=1);

namespace MarfaTech\Bundle\RabbitQueueBundle\Tests\Command;

use MarfaTech\Bundle\RabbitQueueBundle\Client\RabbitMqClient;
use MarfaTech\Bundle\RabbitQueueBundle\Command\ConsumerRunCommand;
use MarfaTech\Bundle\RabbitQueueBundle\Registry\ConsumerRegistry;
use MarfaTech\Bundle\RabbitQueueBundle\Registry\DefinitionRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

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
}
