<?php

declare(strict_types=1);

namespace MarfaTech\Bundle\RabbitQueueBundle\DependencyInjection\Compiler;

use MarfaTech\Bundle\RabbitQueueBundle\Factory\ConnectionFactory;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class AMQPStreamConnectionPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $hosts = $container->getParameter('marfatech_rabbit_queue.hosts');
        $container->getParameterBag()->remove('marfatech_rabbit_queue.hosts');

        $options = $container->getParameter('marfatech_rabbit_queue.options');
        $container->getParameterBag()->remove('marfatech_rabbit_queue.options');

        $isLazyConnection = $options['lazy_connection'];
        unset($options['lazy_connection']);

        $connectionDefinition = new Definition(AMQPStreamConnection::class);
        $connectionDefinition
            ->setFactory([ConnectionFactory::class, 'createFirstSuccessfulAMQPStreamConnection'])
            ->setArguments([$hosts, $options])
            ->setLazy($isLazyConnection)
        ;

        $container->setDefinition(AMQPStreamConnection::class, $connectionDefinition);
    }
}
