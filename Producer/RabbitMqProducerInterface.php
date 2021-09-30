<?php

declare(strict_types=1);

namespace MarfaTech\Bundle\RabbitQueueBundle\Producer;

interface RabbitMqProducerInterface
{
    public function put(
        string $queueName,
        $data,
        array $options = [],
        ?string $routingKey = null,
        array $properties = []
    );
}
