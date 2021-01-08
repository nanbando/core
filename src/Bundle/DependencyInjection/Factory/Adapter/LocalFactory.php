<?php

namespace Nanbando\Bundle\DependencyInjection\Factory\Adapter;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Nanbando\Bundle\DependencyInjection\Factory\AdapterFactoryInterface;
use League\Flysystem\Adapter\Local;

class LocalFactory implements AdapterFactoryInterface
{
    public function getKey()
    {
        return 'local';
    }

    public function create(ContainerBuilder $container, $id, array $config)
    {
        $container
            ->setDefinition($id, new ChildDefinition('nanbando.adapter.local'))
            ->setLazy($config['lazy'] ?? false)
            ->replaceArgument(0, $config['directory'])
            ->replaceArgument(1, $config['writeFlags'] ?? LOCK_EX)
            ->replaceArgument(2, $config['linkHandling'] ?? Local::DISALLOW_LINKS)
            ->replaceArgument(3, $config['permissions'] ?? [
                'file' => [
                    'public' => 0644,
                    'private' => 0600,
                ],
                'dir' => [
                    'public' => 0755,
                    'private' => 0700,
                ],
            ])
        ;
    }

    public function addConfiguration(NodeDefinition $node)
    {
        $node
            ->children()
                ->booleanNode('lazy')->defaultValue(false)->end()
                ->scalarNode('directory')->isRequired()->end()
                ->scalarNode('writeFlags')->defaultValue(LOCK_EX)->end()
                ->scalarNode('linkHandling')->defaultValue(Local::DISALLOW_LINKS)->end()
                ->arrayNode('permissions')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('file')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('public')->defaultValue(0644)->end()
                                ->scalarNode('private')->defaultValue(0600)->end()
                            ->end()
                        ->end()
                        ->arrayNode('dir')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('public')->defaultValue(0755)->end()
                                ->scalarNode('private')->defaultValue(0700)->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
