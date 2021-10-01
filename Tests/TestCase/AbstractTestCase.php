<?php

declare(strict_types=1);

namespace MarfaTech\Bundle\RabbitQueueBundle\Tests\TestCase;

use MarfaTech\Bundle\RabbitQueueBundle\Definition\DefinitionInterface;
use MarfaTech\Bundle\RabbitQueueBundle\Hydrator\JsonHydrator;
use MarfaTech\Bundle\RabbitQueueBundle\Registry\HydratorRegistry;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\TestCase;

use function array_diff;

class AbstractTestCase extends TestCase
{
    protected const TEST_PARAMS = ['type' => 'test'];
    protected const TEST_MESSAGE = '{"test": "test"}';
    protected const TEST_EXCHANGE = 'test_exchange';
    protected const TEST_QUEUE_NAME = 'test_queue';
    protected const TEST_ROUTING = 'test.routing';

    public function createDefinitionMock(string $queueName, string $entryPointName, int $queueType): DefinitionInterface
    {
        return new class ($queueName, $entryPointName, $queueType) implements DefinitionInterface {
            private string $entryPointName;
            private int $queueType;
            private static string $queueName;

            public function __construct(string $queueName, string $entryPointName, int $queueType)
            {
                $this->entryPointName = $entryPointName;
                $this->queueType = $queueType;
                self::$queueName = $queueName;
            }

            public function init(AMQPStreamConnection $connection): void
            {
            }

            public function getEntryPointName(): string
            {
                return $this->entryPointName;
            }

            public function getQueueType(): int
            {
                return $this->queueType;
            }

            public static function getQueueName(): string
            {
                return self::$queueName;
            }
        };
    }

    protected function createHydratorRegistryMock(): HydratorRegistry
    {
        $hydratorRegistry = $this->createMock(HydratorRegistry::class);
        $hydratorRegistry
            ->method('getHydrator')
            ->with(JsonHydrator::KEY)
            ->willReturn(new JsonHydrator())
        ;

        return $hydratorRegistry;
    }

    protected static function getAmqpMockCallback(): Callback
    {
        return self::callback(static function ($value) {
            $isAmqpMessage = $value instanceof AMQPMessage;

            return $isAmqpMessage && empty(array_diff(static::TEST_PARAMS, $value->get_properties()));
        });
    }
}
