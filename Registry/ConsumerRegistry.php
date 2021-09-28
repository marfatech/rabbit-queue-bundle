<?php

declare(strict_types=1);

namespace MarfaTech\Bundle\RabbitQueueBundle\Registry;

use MarfaTech\Bundle\RabbitQueueBundle\Consumer\AbstractConsumer;
use MarfaTech\Bundle\RabbitQueueBundle\Consumer\ConsumerInterface;
use MarfaTech\Bundle\RabbitQueueBundle\Exception\ConsumerNotFoundException;
use Symfony\Contracts\Service\ServiceProviderInterface;

use function sprintf;

class ConsumerRegistry
{
    private ServiceProviderInterface $consumerList;

    public function __construct(ServiceProviderInterface $consumerList)
    {
        $this->consumerList = $consumerList;
    }

    /**
     * @throws ConsumerNotFoundException
     */
    public function getConsumer(string $name): AbstractConsumer
    {
        if ($this->consumerList->has($name)) {
            return $this->consumerList->get($name);
        }

        throw new ConsumerNotFoundException(sprintf('Consumer with name "%s" not found', $name));
    }

    /**
     * @return ConsumerInterface[]
     */
    public function getConsumerList(): array
    {
        $consumerList = [];

        foreach ($this->consumerList->getProvidedServices() as $key => $name) {
            $consumerList[$key] = $this->consumerList->get($key);
        }

        return $consumerList;
    }
}
