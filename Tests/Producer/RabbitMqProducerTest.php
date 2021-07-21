<?php

declare(strict_types=1);

namespace MarfaTech\Bundle\RabbitQueueBundle\Tests\Producer;

use MarfaTech\Bundle\RabbitQueueBundle\Definition\ExampleDefinition;
use MarfaTech\Bundle\RabbitQueueBundle\Definition\ExampleFifoDefinition;
use MarfaTech\Bundle\RabbitQueueBundle\Enum\QueueEnum;
use MarfaTech\Bundle\RabbitQueueBundle\Hydrator\JsonHydrator;
use MarfaTech\Bundle\RabbitQueueBundle\Producer\RabbitMqProducer;
use MarfaTech\Bundle\RabbitQueueBundle\Registry\DefinitionRegistry;
use MarfaTech\Bundle\RabbitQueueBundle\Registry\HydratorRegistry;
use MarfaTech\Bundle\RabbitQueueBundle\Registry\PublisherRegistry;
use PHPUnit\Framework\TestCase;

class RabbitMqProducerTest extends TestCase
{
    private const TEST_MESSAGE = ['message' => 'test'];

    private RabbitMqProducer $producer;

    protected function setUp(): void
    {
        $publisherRegistry = $this->createMock(PublisherRegistry::class);

        $definitionRegistry = $this->createMock(DefinitionRegistry::class);
        $definitionRegistry
            ->method('getDefinition')
            ->willReturnMap([
                [QueueEnum::EXAMPLE_DEDUPLICATE_DELAY, new ExampleDefinition()],
                [QueueEnum::EXAMPLE_FIFO, new ExampleFifoDefinition()],
            ])
        ;

        $hydratorRegistry = $this->createMock(HydratorRegistry::class);
        $hydratorRegistry
            ->method('getHydrator')
            ->with(JsonHydrator::KEY)
            ->willReturn(new JsonHydrator())
        ;

        $this->producer = new RabbitMqProducer($definitionRegistry, $hydratorRegistry, $publisherRegistry, JsonHydrator::KEY);
    }

    public function testPut(): void
    {
        $this->producer->put(QueueEnum::EXAMPLE_FIFO, self::TEST_MESSAGE);

        self::assertTrue(true);
    }
}
