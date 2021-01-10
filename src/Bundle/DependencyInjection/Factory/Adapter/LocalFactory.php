<?php

namespace Nanbando\Bundle\DependencyInjection\Factory\Adapter;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Nanbando\Bundle\DependencyInjection\Factory\AdapterFactoryInterface;
use League\Flysystem\Adapter\Local;

class LocalFactory implements AdapterFactoryInterface
{
    public function getKey(): string
    {
        return 'local';
    }

    public function create(ContainerBuilder $container, $id, array $config): void
    {
        $container
            ->setDefinition($id, new ChildDefinition('nanbando.adapter.local'))
            ->replaceArgument(0, $config['directory']);
    }

    public function addConfiguration(NodeDefinition $node): void
    {
        $node
            ->children()
                ->scalarNode('directory')->isRequired()->end()
        ;
    }
}
