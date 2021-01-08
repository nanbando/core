<?php

namespace Nanbando\Bundle\DependencyInjection\Factory\Adapter;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Nanbando\Bundle\DependencyInjection\Factory\AdapterFactoryInterface;

class MemoryAdapterFactory implements AdapterFactoryInterface
{
    public function getKey()
    {
        return 'memory';
    }

    public function create(ContainerBuilder $container, $id, array $config)
    {
        $container
            ->setDefinition($id, new ChildDefinition('nanbando.adapter.memory'))
        ;
    }

    public function addConfiguration(NodeDefinition $node)
    {
        $node
            ->children()
            ->end()
        ;
    }
}
