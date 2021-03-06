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

class ReleasePartialException extends RuntimeException
{
    /**
     * @var int[]
     */
    private array $releaseDeliveryTagList;

    /**
     * @param int[] $releaseDeliveryTagList
     *
     * @throws RabbitQueueException
     */
    public function __construct(array $releaseDeliveryTagList)
    {
        foreach ($releaseDeliveryTagList as $deliveryTag) {
            if (!is_int($deliveryTag)) {
                throw new RabbitQueueException('Delivery tag must be integer');
            }
        }

        $this->releaseDeliveryTagList = $releaseDeliveryTagList;

        parent::__construct('Consumer release partial message list');
    }

    /**
     * @return int[]
     */
    public function getReleaseDeliveryTagList(): array
    {
        return $this->releaseDeliveryTagList;
    }
}
