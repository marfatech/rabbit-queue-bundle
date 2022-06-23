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

namespace MarfaTech\Bundle\RabbitQueueBundle\DependencyInjection;

use Exception;
use MarfaTech\Bundle\RabbitQueueBundle\Consumer\ConsumerInterface;
use MarfaTech\Bundle\RabbitQueueBundle\Definition\DefinitionInterface;
use MarfaTech\Bundle\RabbitQueueBundle\Hydrator\HydratorInterface;
use MarfaTech\Bundle\RabbitQueueBundle\Publisher\PublisherInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

use function current;

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
        $this->setConnectionParams($container, $config);
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

    private function setConnectionParams(ContainerBuilder $container, array $config): void
    {
        $hosts = [];

        foreach ($config['connections'] as $connection) {
            $hosts[] = [
                'host' => $connection['host'],
                'port' => $connection['port'],
                'user' => $connection['username'],
                'password' => $connection['password'],
                'vhost' => $connection['vhost'],
            ];
        }

        $defaultConnection = current($config['connections']);
        $container->setParameter('marfatech_rabbit_queue.connection.host', $defaultConnection['host']);
        $container->setParameter('marfatech_rabbit_queue.connection.port', $defaultConnection['port']);
        $container->setParameter('marfatech_rabbit_queue.connection.username', $defaultConnection['username']);
        $container->setParameter('marfatech_rabbit_queue.connection.password', $defaultConnection['password']);
        $container->setParameter('marfatech_rabbit_queue.connection.vhost', $defaultConnection['vhost']);


        if (isset($config['options'])) {
            $options = $config['options'];

            $container->setParameter(
                'marfatech_rabbit_queue.connection.connection_timeout',
                $options['connection_timeout']
            );

            $container->setParameter(
                'marfatech_rabbit_queue.connection.read_write_timeout',
                $options['read_write_timeout']
            );

            $container->setParameter(
                'marfatech_rabbit_queue.connection.heartbeat',
                $options['heartbeat']
            );
        } else {
            $options = [
                'connection_timeout' => $defaultConnection['connection_timeout'],
                'read_write_timeout' => $defaultConnection['read_write_timeout'],
                'heartbeat' => $defaultConnection['heartbeat'],
            ];

            $container->setParameter(
                'marfatech_rabbit_queue.connection.connection_timeout',
                $defaultConnection['connection_timeout']
            );

            $container->setParameter(
                'marfatech_rabbit_queue.connection.read_write_timeout',
                $defaultConnection['read_write_timeout']
            );

            $container->setParameter(
                'marfatech_rabbit_queue.connection.heartbeat',
                $defaultConnection['heartbeat']
            );
        }

        $container->setParameter('marfatech_rabbit_queue.hosts', $hosts);
        $container->setParameter('marfatech_rabbit_queue.options', $options);
    }

    private function setConsumerParams(ContainerBuilder $container, array $consumerConfig): void
    {
        $container->setParameter('marfatech_rabbit_queue.consumer.idle_timeout', $consumerConfig['idle_timeout']);
        $container->setParameter('marfatech_rabbit_queue.consumer.wait_timeout', $consumerConfig['wait_timeout']);
        $container->setParameter('marfatech_rabbit_queue.consumer.batch_timeout', $consumerConfig['batch_timeout']);
    }
}
