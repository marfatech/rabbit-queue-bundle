<?php

declare(strict_types=1);

namespace MarfaTech\Bundle\RabbitQueueBundle\Exception;

use RuntimeException;

use function is_int;

class RewindPartialException extends RuntimeException
{
    /**
     * @var int[]
     */
    private array $rewindDeliveryTagList;
    private array $deliveryTagContextList;

    /**
     * @param int[] $rewindDeliveryTagList
     * @param array $deliveryTagContextList
     *
     * @throws RabbitQueueException
     */
    public function __construct(array $rewindDeliveryTagList, array $deliveryTagContextList = [])
    {
        foreach ($rewindDeliveryTagList as $deliveryTag) {
            if (!is_int($deliveryTag)) {
                throw new RabbitQueueException('Delivery tag must be integer');
            }
        }

        $this->rewindDeliveryTagList = $rewindDeliveryTagList;
        $this->deliveryTagContextList = $deliveryTagContextList;

        parent::__construct('Consumer rewind partial message list');
    }

    /**
     * @return int[]
     */
    public function getRewindDeliveryTagList(): array
    {
        return $this->rewindDeliveryTagList;
    }

    public function getDeliveryTagContextList(): array
    {
        return $this->deliveryTagContextList;
    }
}
