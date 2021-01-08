<?php

namespace Nanbando\Bundle\DependencyInjection;

use League\Flysystem\AdapterInterface;
use Nanbando\Bundle\DependencyInjection\Factory\AdapterFactoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
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
     * @var AdapterFactoryInterface[]
     */
    protected $adapterFactories;

    public function __construct(array $adapterFactories)
    {
        $this->adapterFactories = $adapterFactories;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('nanbando');

        $rootNode = $treeBuilder->getRootNode();
        $this->addAdapterSection($rootNode);
        $this->addFilesystemSection($rootNode);

        $rootNode->children()
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
                            ->arrayNode('process')
                                ->defaultValue([])
                                ->prototype('scalar')->end()
                            ->end()
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
                                        ->arrayNode('process')
                                            ->defaultValue([])
                                            ->prototype('scalar')->end()
                                        ->end()
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

    private function addAdapterSection(ArrayNodeDefinition $node)
    {
        $adapterNodeBuilder = $node
            ->fixXmlConfig('adapter')
            ->children()
                ->arrayNode('adapters')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                    ->performNoDeepMerging()
                    ->children()
        ;

        foreach ($this->adapterFactories as $name => $factory) {
            $factoryNode = $adapterNodeBuilder->arrayNode($name)->canBeUnset();

            $factory->addConfiguration($factoryNode);
        }
    }

    private function addFilesystemSection(ArrayNodeDefinition $node)
    {
        $supportedVisibilities = array(
            AdapterInterface::VISIBILITY_PRIVATE,
            AdapterInterface::VISIBILITY_PUBLIC,
        );

        $node
            ->fixXmlConfig('filesystem')
            ->children()
                ->arrayNode('filesystems')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                    ->children()
                        ->booleanNode('disable_asserts')
                            ->defaultFalse()
                        ->end()
                        ->arrayNode('plugins')->treatNullLike(array())->prototype('scalar')->end()->end()
                        ->scalarNode('adapter')->isRequired()->end()
                        ->scalarNode('alias')->defaultNull()->end()
                        ->scalarNode('mount')->defaultNull()->end()
                        ->arrayNode('stream_wrapper')
                            ->beforeNormalization()
                                ->ifString()->then(function ($protocol) {
                                    return ['protocol' => $protocol];
                                })
                            ->end()
                            ->children()
                                ->scalarNode('protocol')->isRequired()->end()
                                ->arrayNode('configuration')
                                    ->children()
                                        ->arrayNode('permissions')
                                            ->isRequired()
                                            ->children()
                                                ->arrayNode('dir')
                                                    ->isRequired()
                                                    ->children()
                                                        ->integerNode('private')->isRequired()->end()
                                                        ->integerNode('public')->isRequired()->end()
                                                    ->end()
                                                ->end()
                                                ->arrayNode('file')
                                                    ->isRequired()
                                                    ->children()
                                                        ->integerNode('private')->isRequired()->end()
                                                        ->integerNode('public')->isRequired()->end()
                                                    ->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                        ->arrayNode('metadata')
                                            ->isRequired()
                                            ->requiresAtLeastOneElement()
                                            ->prototype('scalar')->cannotBeEmpty()->end()
                                        ->end()
                                        ->integerNode('public_mask')->isRequired()->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->scalarNode('visibility')
                            ->validate()
                            ->ifNotInArray($supportedVisibilities)
                            ->thenInvalid('The visibility %s is not supported.')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
