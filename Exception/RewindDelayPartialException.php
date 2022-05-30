<?php

declare(strict_types=1);

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
    private array $contextInfoByTagList;

    /**
     * @param int[] $rewindDeliveryTagList
     * @param int $delay
     * @param array $contextInfoByTagList
     *
     * @throws RabbitQueueException
     */
    public function __construct(array $rewindDeliveryTagList, int $delay, array $contextInfoByTagList = [])
    {
        foreach ($rewindDeliveryTagList as $deliveryTag) {
            if (!is_int($deliveryTag)) {
                throw new RabbitQueueException('Delivery tag must be integer');
            }
        }

        $this->rewindDeliveryTagList = $rewindDeliveryTagList;
        $this->delay = $delay;
        $this->contextInfoByTagList = $contextInfoByTagList;

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

    public function getContextInfoByTagList(): array
    {
        return $this->contextInfoByTagList;
    }
}
