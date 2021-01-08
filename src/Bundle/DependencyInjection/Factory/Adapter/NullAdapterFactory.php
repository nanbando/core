<?php

namespace Nanbando\Bundle\DependencyInjection\Factory\Adapter;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Nanbando\Bundle\DependencyInjection\Factory\AdapterFactoryInterface;

class NullAdapterFactory implements AdapterFactoryInterface
{
    public function getKey()
    {
        return 'nulladapter';
    }

    public function create(ContainerBuilder $container, $id, array $config)
    {
        $container
            ->setDefinition($id, new ChildDefinition('nanbando.adapter.nulladapter'))
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
