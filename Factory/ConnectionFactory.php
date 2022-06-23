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

namespace MarfaTech\Bundle\RabbitQueueBundle\Factory;

use Exception;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class ConnectionFactory
{
    /**
     * @throws Exception
     */
    public static function createFirstSuccessfulAMQPStreamConnection(array $hosts, array $options): AMQPStreamConnection
    {
        return AMQPStreamConnection::create_connection($hosts, $options);
    }
}
