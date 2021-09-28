<?php

declare(strict_types=1);

namespace MarfaTech\Bundle\RabbitQueueBundle\Tests\Publisher;

use MarfaTech\Bundle\RabbitQueueBundle\Client\RabbitMqClient;
use MarfaTech\Bundle\RabbitQueueBundle\Enum\QueueOptionEnum;
use MarfaTech\Bundle\RabbitQueueBundle\Enum\QueueTypeEnum;
use MarfaTech\Bundle\RabbitQueueBundle\Hydrator\JsonHydrator;
use MarfaTech\Bundle\RabbitQueueBundle\Publisher\FifoPublisher;
use MarfaTech\Bundle\RabbitQueueBundle\Tests\TestCase\AbstractTestCase;
use PhpAmqpLib\Message\AMQPMessage;

class FifoPublisherTest extends AbstractTestCase
{
    public const QUEUE_TYPE = QueueTypeEnum::FIFO;

    public function testPublish(): void
    {
        $definition = $this->createDefinitionMock(self::TEST_QUEUE_NAME, self::TEST_EXCHANGE, self::QUEUE_TYPE);
        $hydratorRegistry = $this->createHydratorRegistryMock();

        $client = $this->createMock(RabbitMqClient::class);
        $client->expects(self::once())
            ->method('publish')
            ->with(self::isInstanceOf(AMQPMessage::class), '', self::TEST_QUEUE_NAME)
        ;

        $publisher = new FifoPublisher($client, $hydratorRegistry, JsonHydrator::KEY);

        $publisher->publish($definition, self::TEST_MESSAGE);

        self::assertTrue(true);
    }

    public function testPublishWithRouting(): void
    {
        $definition = $this->createDefinitionMock(self::TEST_QUEUE_NAME, self::TEST_EXCHANGE, self::QUEUE_TYPE);
        $hydratorRegistry = $this->createHydratorRegistryMock();

        $client = $this->createMock(RabbitMqClient::class);
        $client->expects(self::once())
            ->method('publish')
            ->with(self::isInstanceOf(AMQPMessage::class), '', self::TEST_ROUTING)
        ;

        $publisher = new FifoPublisher($client, $hydratorRegistry, JsonHydrator::KEY);

        $options = [QueueOptionEnum::ROUTING_KEY => self::TEST_ROUTING];

        $publisher->publish($definition, self::TEST_MESSAGE, $options);

        self::assertTrue(true);
    }
}
