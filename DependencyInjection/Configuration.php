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

use MarfaTech\Bundle\RabbitQueueBundle\Hydrator\JsonHydrator;
use Symfony\Component\Config\Definition\BaseNode;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use function method_exists;

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
                ->arrayNode('connections')
                ->isRequired()
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('host')->isRequired()->cannotBeEmpty()->end()
                            ->integerNode('port')->defaultValue(5672)->end()
                            ->scalarNode('username')->isRequired()->cannotBeEmpty()->end()
                            ->scalarNode('password')->isRequired()->cannotBeEmpty()->end()
                            ->scalarNode('vhost')->defaultValue('/')->end()
                            ->integerNode('connection_timeout')
                                ->defaultValue(3)
                                ->setDeprecated(
                                    ...$this->getDeprecationMsg(
                                        '"connection_timeout" key in "connections" is deprecated. Put it in "options".',
                                        '3.1.1',
                                    )
                                )
                            ->end()
                            ->integerNode('read_write_timeout')
                                ->defaultValue(3)
                                ->setDeprecated(
                                    ...$this->getDeprecationMsg(
                                        '"read_write_timeout" key in "connections" is deprecated. Put it in "options".',
                                        '3.1.1',
                                    )
                                )
                            ->end()
                            ->integerNode('heartbeat')
                                ->defaultValue(0)
                                ->setDeprecated(
                                    ...$this->getDeprecationMsg(
                                        '"heartbeat" key in "connections" is deprecated. Put it in "options".',
                                        '3.1.1',
                                    )
                                )
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('options')
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

    private function getDeprecationMsg(string $message, string $version): array
    {
        if (method_exists(BaseNode::class, 'getDeprecation')) {
            return [
                'marfatech/rabbit-queue-bundle',
                $version,
                $message,
            ];
        }

        return [$message];
    }
}
