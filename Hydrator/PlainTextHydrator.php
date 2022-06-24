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

class PlainTextHydrator implements HydratorInterface
{
    public const KEY = 'text/plain';

    public function hydrate(string $dataString): string
    {
        return $dataString;
    }

    /**
     * {@inheritDoc}
     */
    public function dehydrate($data): string
    {
        return $data;
    }

    public static function getKey(): string
    {
        return static::KEY;
    }
}
