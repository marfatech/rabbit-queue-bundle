<?php

declare(strict_types=1);

namespace MarfaTech\Bundle\RabbitQueueBundle\Publisher;

use MarfaTech\Bundle\RabbitQueueBundle\Client\RabbitMqClient;
use MarfaTech\Bundle\RabbitQueueBundle\Definition\DefinitionInterface;
use MarfaTech\Bundle\RabbitQueueBundle\Enum\QueueTypeEnum;
use MarfaTech\Bundle\RabbitQueueBundle\Exception\HydratorNotFoundException;
use MarfaTech\Bundle\RabbitQueueBundle\Registry\HydratorRegistry;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

use function array_merge;

abstract class AbstractPublisher implements PublisherInterface
{
    public const QUEUE_TYPE = QueueTypeEnum::FIFO;
    private const DEFAULT_NAME = '';

    protected HydratorRegistry $hydratorRegistry;
    protected string $hydratorName;
    protected RabbitMqClient $client;

    public function __construct(RabbitMqClient $client, HydratorRegistry $hydratorRegistry, string $hydratorName)
    {
        $this->hydratorRegistry = $hydratorRegistry;
        $this->hydratorName = $hydratorName;
        $this->client = $client;
    }

    abstract protected function prepareOptions(DefinitionInterface $definition, array $options): array;

    /**
     * @throws HydratorNotFoundException
     */
    public function publish(
        DefinitionInterface $definition,
        string $dataString,
        array $headers = [],
        ?string $routingKey = null,
        array $properties = []
    ): void {
        $exchangeName = $this->getDefinitionExchangeName($definition);
        $route = !empty($routingKey) ? $routingKey : $this->getDefinitionQueueName($definition);

        $defaultProperties = [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            'content_type' => $this->hydratorRegistry->getHydrator($this->hydratorName)::getKey(),
        ];

        $message = new AMQPMessage($dataString, array_merge($defaultProperties, $properties));

        $amqpTableOptions = $this->prepareOptions($definition, $headers);

        if (!empty($amqpTableOptions)) {
            $message->set('application_headers', new AMQPTable($amqpTableOptions));
        }

        $this->client->publish($message, $exchangeName, $route);
    }

    abstract public static function getQueueType(): string;

    protected function getDefinitionExchangeName(DefinitionInterface $definition): string
    {
        return $definition->getQueueType() & (QueueTypeEnum::ROUTER | QueueTypeEnum::DELAY)
            ? $definition->getEntryPointName()
            : self::DEFAULT_NAME
        ;
    }

    protected function getDefinitionQueueName(DefinitionInterface $definition): string
    {
        return $definition->getQueueType() & QueueTypeEnum::DELAY
            ? self::DEFAULT_NAME
            : $definition::getQueueName()
        ;
    }
}
