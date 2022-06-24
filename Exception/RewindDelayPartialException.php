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

namespace MarfaTech\Bundle\RabbitQueueBundle\Exception;

use RuntimeException;

use function is_int;

class RewindDelayPartialException extends RuntimeException
{
    /**
     * @var int[]
     */
    private array $rewindDeliveryTagList;
    private int $delay;
    private array $deliveryTagContextList;

    /**
     * @param int[] $rewindDeliveryTagList
     * @param int $delay
     * @param array $deliveryTagContextList
     *
     * @throws RabbitQueueException
     */
    public function __construct(array $rewindDeliveryTagList, int $delay, array $deliveryTagContextList = [])
    {
        foreach ($rewindDeliveryTagList as $deliveryTag) {
            if (!is_int($deliveryTag)) {
                throw new RabbitQueueException('Delivery tag must be integer');
            }
        }

        $this->rewindDeliveryTagList = $rewindDeliveryTagList;
        $this->delay = $delay;
        $this->deliveryTagContextList = $deliveryTagContextList;

        parent::__construct('Consumer rewind delay partial messageList');
    }

    /**
     * @return int[]
     */
    public function getRewindDeliveryTagList(): array
    {
        return $this->rewindDeliveryTagList;
    }

    public function getDelay(): int
    {
        return $this->delay;
    }

    public function getDeliveryTagContextList(): array
    {
        return $this->deliveryTagContextList;
    }
}
