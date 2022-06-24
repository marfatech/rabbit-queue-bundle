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

namespace MarfaTech\Bundle\RabbitQueueBundle\Tests\Publisher;

use MarfaTech\Bundle\RabbitQueueBundle\Client\RabbitMqClient;
use MarfaTech\Bundle\RabbitQueueBundle\Enum\QueueTypeEnum;
use MarfaTech\Bundle\RabbitQueueBundle\Exception\RabbitQueueException;
use MarfaTech\Bundle\RabbitQueueBundle\Hydrator\JsonHydrator;
use MarfaTech\Bundle\RabbitQueueBundle\Publisher\DeduplicateDelayPublisher;
use MarfaTech\Bundle\RabbitQueueBundle\Tests\TestCase\AbstractTestCase;
use PhpAmqpLib\Message\AMQPMessage;

class DeduplicateDelayPublisherTest extends AbstractTestCase
{
    protected const TEST_OPTIONS = ['delay' => 10, 'key' => 'unique_key'];
    protected const QUEUE_TYPE = QueueTypeEnum::FIFO | QueueTypeEnum::DELAY | QueueTypeEnum::DEDUPLICATE;

    public function testPublish(): void
    {
        $definition = $this->createDefinitionMock(self::TEST_QUEUE_NAME, self::TEST_EXCHANGE, self::QUEUE_TYPE);
        $hydratorRegistry = $this->createHydratorRegistryMock();

        $client = $this->createMock(RabbitMqClient::class);
        $client->expects(self::once())
            ->method('publish')
            ->with(self::getAmqpMockCallback(), self::TEST_EXCHANGE, '')
        ;

        $publisher = new DeduplicateDelayPublisher($client, $hydratorRegistry, JsonHydrator::KEY);

        $publisher->publish($definition, self::TEST_MESSAGE, self::TEST_OPTIONS, null, self::TEST_PARAMS);

        self::assertTrue(true);
    }

    public function testPublishWithRouting(): void
    {
        $definition = $this->createDefinitionMock(self::TEST_QUEUE_NAME, self::TEST_EXCHANGE, self::QUEUE_TYPE);
        $hydratorRegistry = $this->createHydratorRegistryMock();

        $client = $this->createMock(RabbitMqClient::class);
        $client->expects(self::once())
            ->method('publish')
            ->with(self::isInstanceOf(AMQPMessage::class), self::TEST_EXCHANGE, '')
        ;

        $publisher = new DeduplicateDelayPublisher($client, $hydratorRegistry, JsonHydrator::KEY);

        $publisher->publish($definition, self::TEST_MESSAGE, self::TEST_OPTIONS);

        self::assertTrue(true);
    }

    /**
     * @dataProvider invalidOptionsProvider
     */
    public function testPublishInvalidOptions(array $options): void
    {
        $this->expectException(RabbitQueueException::class);

        $definition = $this->createDefinitionMock(self::TEST_QUEUE_NAME, self::TEST_EXCHANGE, self::QUEUE_TYPE);
        $client = $this->createMock(RabbitMqClient::class);
        $hydratorRegistry = $this->createHydratorRegistryMock();

        $publisher = new DeduplicateDelayPublisher($client, $hydratorRegistry, JsonHydrator::KEY);

        $publisher->publish($definition, self::TEST_MESSAGE, $options);
    }

    public function invalidOptionsProvider(): array
    {
        return [
            'empty options'  => [[]],
            'only key option' => [['key' => 'unique_key']],
            'only delay option' => [['delay' => 1]],
            'invalid string delay option' => [['delay' => 'test', 'key' => 'unique_key']],
        ];
    }
}
