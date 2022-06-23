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

namespace MarfaTech\Bundle\RabbitQueueBundle\Publisher;

use MarfaTech\Bundle\RabbitQueueBundle\Definition\DefinitionInterface;

interface PublisherInterface
{
    public const TAG = 'marfatech_rabbit_queue.publisher';

    public function publish(
        DefinitionInterface $definition,
        string $dataString,
        array $options = [],
        ?string $routingKey = null,
        array $properties = []
    ): void;

    public static function getQueueType(): string;
}
