<?php

namespace Nanbando\Bundle\DependencyInjection\Factory\Adapter;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Reference;
use Nanbando\Bundle\DependencyInjection\Factory\AdapterFactoryInterface;

class GridFSFactory implements AdapterFactoryInterface
{
    public function getKey()
    {
        return 'gridfs';
    }

    public function create(ContainerBuilder $container, $id, array $config)
    {
        $definition = $container
            ->setDefinition($id, new ChildDefinition('nanbando.adapter.gridfs'))
            ->replaceArgument(0, new Reference($config['client']))
        ;
    }

    public function addConfiguration(NodeDefinition $node)
    {
        $node
            ->children()
                ->scalarNode('client')->isRequired()->end()
            ->end()
        ;
    }
}
