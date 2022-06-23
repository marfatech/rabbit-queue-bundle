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

use MarfaTech\Bundle\RabbitQueueBundle\Exception\PublisherNotFoundException;
use MarfaTech\Bundle\RabbitQueueBundle\Publisher\AbstractPublisher;
use Symfony\Contracts\Service\ServiceProviderInterface;

use function sprintf;

class PublisherRegistry
{
    private ServiceProviderInterface $publisherRegistry;

    public function __construct(ServiceProviderInterface $publisherRegistry)
    {
        $this->publisherRegistry = $publisherRegistry;
    }

    /**
     * @throws PublisherNotFoundException
     */
    public function getPublisher(int $queueType): AbstractPublisher
    {
        $queueTypeId = (string) $queueType;

        if ($this->publisherRegistry->has($queueTypeId)) {
            return $this->publisherRegistry->get($queueTypeId);
        }

        throw new PublisherNotFoundException(sprintf('Publisher for queue type "%s" not found', $queueType));
    }
}
