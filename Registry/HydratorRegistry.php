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

namespace MarfaTech\Bundle\RabbitQueueBundle\Registry;

use MarfaTech\Bundle\RabbitQueueBundle\Exception\HydratorNotFoundException;
use MarfaTech\Bundle\RabbitQueueBundle\Hydrator\HydratorInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;

use function sprintf;

class HydratorRegistry
{
    private ServiceProviderInterface $hydratorList;

    public function __construct(ServiceProviderInterface $hydratorList)
    {
        $this->hydratorList = $hydratorList;
    }

    /**
     * @throws HydratorNotFoundException
     */
    public function getHydrator(string $hydratorKey): HydratorInterface
    {
        if ($this->hydratorList->has($hydratorKey)) {
            return $this->hydratorList->get($hydratorKey);
        }

        throw new HydratorNotFoundException(sprintf('Hydrator with key "%s" not found', $hydratorKey));
    }
}
