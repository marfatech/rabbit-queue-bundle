<?php

declare(strict_types=1);

namespace MarfaTech\Bundle\RabbitQueueBundle\Factory;

use Exception;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class ConnectionFactory
{
    /**
     * @throws Exception
     */
    public static function createAMQPStreamConnection(array $hosts, array $options): AMQPStreamConnection
    {
        return AMQPStreamConnection::create_connection($hosts, $options);
    }
}
