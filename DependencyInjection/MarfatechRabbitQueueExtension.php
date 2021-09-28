<?php

declare(strict_types=1);

namespace MarfaTech\Bundle\RabbitQueueBundle\DependencyInjection;

use Exception;
use MarfaTech\Bundle\RabbitQueueBundle\Consumer\ConsumerInterface;
use MarfaTech\Bundle\RabbitQueueBundle\Definition\DefinitionInterface;
use MarfaTech\Bundle\RabbitQueueBundle\Hydrator\HydratorInterface;
use MarfaTech\Bundle\RabbitQueueBundle\Publisher\PublisherInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

use function array_key_first;
use function count;

class MarfatechRabbitQueueExtension extends Extension
{
    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('marfatech_rabbit_queue.hydrator_name', $config['hydrator_name']);
        $this->setConnectionParams($container, $config['connections']);
        $this->setConsumerParams($container, $config['consumer']);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        $container
            ->registerForAutoconfiguration(ConsumerInterface::class)
            ->addTag(ConsumerInterface::TAG)
        ;

        $container
            ->registerForAutoconfiguration(DefinitionInterface::class)
            ->addTag(DefinitionInterface::TAG)
        ;

        $container
            ->registerForAutoconfiguration(HydratorInterface::class)
            ->addTag(HydratorInterface::TAG)
        ;

        $container
            ->registerForAutoconfiguration(PublisherInterface::class)
            ->addTag(PublisherInterface::TAG)
        ;
    }

    private function setConnectionParams(ContainerBuilder $container, array $connections): void
    {
        if (count($connections) > 1) {
            $message = 'marfatech_rabbit_queue.connections parameter support only first connection.';

            $exception = new InvalidConfigurationException($message);
            $exception->setPath('marfatech_rabbit_queue.connections');

            throw $exception;
        }

        $connection = $connections[array_key_first($connections)];

        $container->setParameter('marfatech_rabbit_queue.connection.host', $connection['host']);
        $container->setParameter('marfatech_rabbit_queue.connection.port', $connection['port']);
        $container->setParameter('marfatech_rabbit_queue.connection.username', $connection['username']);
        $container->setParameter('marfatech_rabbit_queue.connection.password', $connection['password']);
        $container->setParameter('marfatech_rabbit_queue.connection.vhost', $connection['vhost']);
        $container->setParameter('marfatech_rabbit_queue.connection.connection_timeout', $connection['connection_timeout']);
        $container->setParameter('marfatech_rabbit_queue.connection.read_write_timeout', $connection['read_write_timeout']);
        $container->setParameter('marfatech_rabbit_queue.connection.heartbeat', $connection['heartbeat']);
    }

    private function setConsumerParams(ContainerBuilder $container, array $consumerConfig): void
    {
        $container->setParameter('marfatech_rabbit_queue.consumer.idle_timeout', $consumerConfig['idle_timeout']);
        $container->setParameter('marfatech_rabbit_queue.consumer.wait_timeout', $consumerConfig['wait_timeout']);
    }
}
