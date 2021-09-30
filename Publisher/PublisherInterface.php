<?php

namespace MarfaTech\Bundle\RabbitQueueBundle\Publisher;

use MarfaTech\Bundle\RabbitQueueBundle\Definition\DefinitionInterface;

interface PublisherInterface
{
    public const TAG = 'marfatech_rabbit_queue.publisher';

    public function publish(
        DefinitionInterface $definition,
        string $dataString,
        array $headers = [],
        ?string $routingKey = null,
        array $properties = []
    ): void;

    public static function getQueueType(): string;
}
