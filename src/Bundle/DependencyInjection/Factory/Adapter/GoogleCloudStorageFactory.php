<?php

namespace Nanbando\Bundle\DependencyInjection\Factory\Adapter;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Reference;
use Nanbando\Bundle\DependencyInjection\Factory\AdapterFactoryInterface;

class GoogleCloudStorageFactory implements AdapterFactoryInterface
{
    public function getKey(): string
    {
        return 'googlecloudstorage';
    }

    public function create(ContainerBuilder $container, $id, array $config): void
    {
        $container->setDefinition($id . '.client', new ChildDefinition('nanbando.adapter.googlecloudstorage.client'))
            ->replaceArgument(0, $config['client']);

        $container->setDefinition($id . '.bucket', new ChildDefinition('nanbando.adapter.googlecloudstorage.bucket'))
            ->setFactory([new Reference($id . '.client'), 'bucket'])
            ->replaceArgument(0, $config['bucket']);

        $container
            ->setDefinition($id, new ChildDefinition('nanbando.adapter.googlecloudstorage'))
            ->replaceArgument(0, new Reference($id . '.client'))
            ->replaceArgument(1, new Reference($id . '.bucket'))
            ->replaceArgument(2, $config['prefix'])
        ;
    }

    public function addConfiguration(NodeDefinition $node): void
    {
        $node
            ->children()
                ->arrayNode('client')
                    ->isRequired()
                    ->children()
                        ->scalarNode('projectId')->isRequired()->end()
                        ->scalarNode('keyFilePath')->isRequired()->end()
                    ->end()
                ->end()
                ->scalarNode('bucket')->isRequired()->end()
                ->scalarNode('prefix')->defaultNull()->end()
            ->end()
        ;
    }
}
