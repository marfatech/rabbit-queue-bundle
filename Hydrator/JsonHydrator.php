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

use JsonException;
use MarfaTech\Bundle\RabbitQueueBundle\Exception\RabbitQueueException;

use function json_decode;
use function json_encode;

use const JSON_THROW_ON_ERROR;

class JsonHydrator implements HydratorInterface
{
    public const KEY = 'application/json';

    /**
     * @throws RabbitQueueException
     */
    public function hydrate(string $dataString)
    {
        try {
            return json_decode($dataString, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RabbitQueueException('Invalid hydrate data', 1, $exception);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @throws RabbitQueueException
     */
    public function dehydrate($data): string
    {
        try {
            return json_encode($data, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RabbitQueueException('Invalid dehydrate data', 1, $exception);
        }
    }

    public static function getKey(): string
    {
        return static::KEY;
    }
}
