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

namespace MarfaTech\Bundle\RabbitQueueBundle\Hydrator;

use JsonSerializable;

interface HydratorInterface
{
    public const TAG = 'marfatech_rabbit_queue.hydrator';

    public function hydrate(string $dataString);

    /**
     * @param JsonSerializable|array|integer|string|null|float $data
     *
     * @return string
     */
    public function dehydrate($data): string;

    public static function getKey(): string;
}
