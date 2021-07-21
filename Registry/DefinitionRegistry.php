<?php

declare(strict_types=1);

namespace MarfaTech\Bundle\RabbitQueueBundle\Registry;

use MarfaTech\Bundle\RabbitQueueBundle\Definition\DefinitionInterface;
use MarfaTech\Bundle\RabbitQueueBundle\Exception\DefinitionNotFoundException;
use Symfony\Contracts\Service\ServiceProviderInterface;

use function sprintf;

class DefinitionRegistry
{
    private ServiceProviderInterface $definitionList;

    public function __construct(ServiceProviderInterface $definitionList)
    {
        $this->definitionList = $definitionList;
    }

    /**
     * @throws DefinitionNotFoundException
     */
    public function getDefinition(string $queueName): DefinitionInterface
    {
        if ($this->definitionList->has($queueName)) {
            return $this->definitionList->get($queueName);
        }

        throw new DefinitionNotFoundException(sprintf('Definition with queue name "%s" not found', $queueName));
    }
}
