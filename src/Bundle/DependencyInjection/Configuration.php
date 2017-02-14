<?php

namespace Nanbando\Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Webmozart\PathUtil\Path;

class Configuration implements ConfigurationInterface
{
    const ENV_ENVIRONMENT = 'NANBANDO_ENVIRONMENT';
    const ENV_SSH_USERNAME = 'NANBANDO_SSH_USERNAME';
    const ENV_SSH_PASSWORD = 'NANBANDO_SSH_PASSWORD';
    const ENV_SSH_RSAKEY_FILE = 'NANBANDO_SSH_RSAKEY_FILE';
    const ENV_SSH_RSAKEY_PASSWORD = 'NANBANDO_SSH_RSAKEY_PASSWORD';

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
                ->scalarNode('environment')
                    ->defaultValue('%env(' . self::ENV_ENVIRONMENT . ')%')
                ->end()
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
                ->arrayNode('servers')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->arrayNode('ssh')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('username')
                                        ->defaultValue('%env(' . self::ENV_SSH_USERNAME . ')%')
                                    ->end()
                                    ->scalarNode('password')
                                        ->defaultValue('%env(' . self::ENV_SSH_PASSWORD . ')%')
                                    ->end()
                                    ->arrayNode('rsakey')
                                        ->children()
                                            ->scalarNode('file')
                                                ->defaultValue('%env(' . self::ENV_SSH_RSAKEY_FILE . ')%')
                                            ->end()
                                            ->scalarNode('password')
                                                ->defaultValue('%env(' . self::ENV_SSH_RSAKEY_PASSWORD . ')%')
                                            ->end()
                                        ->end()
                                    ->end()
                                    ->scalarNode('host')->end()
                                    ->scalarNode('port')->defaultValue(22)->end()
                                    ->scalarNode('timeout')->defaultValue(10)->end()
                                ->end()
                            ->end()
                            ->scalarNode('directory')->end()
                            ->scalarNode('executable')->defaultValue('nanbando')->end()
                        ->end()
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
