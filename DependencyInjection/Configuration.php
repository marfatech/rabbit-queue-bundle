<?php

declare(strict_types=1);

namespace MarfaTech\Bundle\RabbitQueueBundle\DependencyInjection;

use MarfaTech\Bundle\RabbitQueueBundle\Hydrator\JsonHydrator;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('marfatech_rabbit_queue');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('hydrator_name')->defaultValue(JsonHydrator::KEY)->end()
                ->arrayNode('hosts')
                ->isRequired()
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('host')->isRequired()->cannotBeEmpty()->end()
                            ->integerNode('port')->defaultValue(5672)->end()
                            ->scalarNode('user')->isRequired()->cannotBeEmpty()->end()
                            ->scalarNode('password')->isRequired()->cannotBeEmpty()->end()
                            ->scalarNode('vhost')->defaultValue('/')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('options')
                ->isRequired()
                    ->children()
                        ->integerNode('connection_timeout')->defaultValue(3)->end()
                        ->integerNode('read_write_timeout')->defaultValue(3)->end()
                        ->integerNode('heartbeat')->defaultValue(0)->end()
                    ->end()
                ->end()
                ->arrayNode('consumer')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('idle_timeout')
                            ->defaultValue(0)
                        ->end()
                        ->integerNode('wait_timeout')
                            ->defaultValue(3)
                        ->end()
                        ->integerNode('batch_timeout')
                            ->defaultValue(0)
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
