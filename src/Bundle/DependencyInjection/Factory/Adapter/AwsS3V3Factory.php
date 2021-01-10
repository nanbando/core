<?php

namespace Nanbando\Bundle\DependencyInjection\Factory\Adapter;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Reference;
use Nanbando\Bundle\DependencyInjection\Factory\AdapterFactoryInterface;

class AwsS3V3Factory implements AdapterFactoryInterface
{
    public function getKey(): string
    {
        return 's3';
    }

    public function create(ContainerBuilder $container, $id, array $config): void
    {
        $container
            ->setDefinition($id . '.client',  new ChildDefinition('nanbando.adapter.awss3v3.client'))
            ->replaceArgument(0, $config['client']);

        $container
            ->setDefinition($id, new ChildDefinition('nanbando.adapter.awss3v3'))
            ->replaceArgument(0, new Reference($id . '.client'))
            ->replaceArgument(1, $config['bucket'])
            ->replaceArgument(2, $config['prefix']);
    }

    public function addConfiguration(NodeDefinition $node): void
    {
        $node
            ->children()
                ->arrayNode('client')
                    ->isRequired()
                    ->children()
                        ->scalarNode('version')->defaultValue('latest')->end()
                        ->scalarNode('region')->isRequired()->end()
                        ->scalarNode('endpoint')->defaultNull()->end()
                        ->arrayNode('credentials')
                            ->isRequired()
                                ->children()
                                    ->scalarNode('key')->isRequired()->end()
                                    ->scalarNode('secret')->isRequired()->end()
                                ->end()
                            ->end()
                    ->end()
                ->end()
                ->scalarNode('bucket')->isRequired()->end()
                ->scalarNode('prefix')->defaultNull()->end()
            ->end()
        ;
    }
}
