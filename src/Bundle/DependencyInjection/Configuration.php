<?php

namespace Nanbando\Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Webmozart\PathUtil\Path;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('nanando');

        $rootNode
            ->children()
                ->scalarNode('name')->defaultValue('nanbando')->end()
                ->enumNode('environment')->values(['prod', 'dev', 'stage'])->defaultNull()->end()
                ->arrayNode('application')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('name')->defaultNull()->end()
                        ->scalarNode('version')->defaultNull()->end()
                        ->arrayNode('options')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('temp')->defaultValue(sys_get_temp_dir())->end()
                ->arrayNode('backup')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('plugin')->end()
                            ->arrayNode('parameter')
                                ->prototype('variable')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('storage')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('local_directory')
                            ->defaultValue(Path::join([Path::getHomeDirectory(), 'nanbando']))
                        ->end()
                        ->scalarNode('remote_service')->end()
                    ->end()
                ->end()
                ->arrayNode('require')
                    ->prototype('variable')->end()
                ->end()
                ->arrayNode('presets')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('application')->end()
                            ->scalarNode('version')->end()
                            ->arrayNode('options')
                                ->prototype('scalar')->end()
                            ->end()
                            ->arrayNode('backup')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('plugin')->end()
                                        ->arrayNode('parameter')
                                            ->prototype('variable')->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
