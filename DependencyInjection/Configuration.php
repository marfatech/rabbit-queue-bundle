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

use Composer\InstalledVersions;
use MarfaTech\Bundle\RabbitQueueBundle\Hydrator\JsonHydrator;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\HttpKernel\Kernel;

use function version_compare;

class Configuration implements ConfigurationInterface
{
    public const OPTIONS_DEFAULT_LIST = [
        'connection_timeout' => 3,
        'read_write_timeout' => 3,
        'heartbeat' => 0,
        'lazy_connection' => false,
    ];

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
                                ->defaultValue(self::OPTIONS_DEFAULT_LIST['connection_timeout'])
                                ->setDeprecated(
                                    ...$this->getDeprecationMessage(
                                        '"connection_timeout" key in "connections" is deprecated. Put it in "options".'
                                    )
                                )
                            ->end()
                            ->integerNode('read_write_timeout')
                                ->defaultValue(self::OPTIONS_DEFAULT_LIST['read_write_timeout'])
                                ->setDeprecated(
                                    ...$this->getDeprecationMessage(
                                        '"read_write_timeout" key in "connections" is deprecated. Put it in "options".'
                                    )
                                )
                            ->end()
                            ->integerNode('heartbeat')
                                ->defaultValue(self::OPTIONS_DEFAULT_LIST['heartbeat'])
                                ->setDeprecated(
                                    ...$this->getDeprecationMessage(
                                        '"heartbeat" key in "connections" is deprecated. Put it in "options".'
                                    )
                                )
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('options')
                ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('connection_timeout')
                            ->defaultValue(self::OPTIONS_DEFAULT_LIST['connection_timeout'])
                        ->end()
                        ->integerNode('read_write_timeout')
                            ->defaultValue(self::OPTIONS_DEFAULT_LIST['read_write_timeout'])
                        ->end()
                        ->integerNode('heartbeat')
                            ->defaultValue(self::OPTIONS_DEFAULT_LIST['heartbeat'])
                        ->end()
                        ->booleanNode('lazy_connection')
                            ->defaultValue(self::OPTIONS_DEFAULT_LIST['lazy_connection'])
                        ->end()
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

    private function getDeprecationMessage(string $message): array
    {
        $packageName = 'marfatech/rabbit-queue-bundle';

        if (version_compare(Kernel::VERSION, '5.1.0', '<')) {
            return [$message];
        }

        return [
            $packageName,
            InstalledVersions::getVersion($packageName),
            $message,
        ];
    }
}
