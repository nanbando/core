<?php

namespace Nanbando\Bundle\DependencyInjection\Factory\Adapter;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Reference;
use Nanbando\Bundle\DependencyInjection\Factory\AdapterFactoryInterface;

class ReplicateFactory implements AdapterFactoryInterface
{
    public function getKey()
    {
        return 'replicate';
    }

    public function create(ContainerBuilder $container, $id, array $config)
    {
        $definition = $container
            ->setDefinition($id, new ChildDefinition('nanbando.adapter.replicate'))
            ->replaceArgument(0, new Reference(sprintf('nanbando.%s_adapter', $config['sourceAdapter'])))
            ->replaceArgument(1, new Reference(sprintf('nanbando.%s_adapter', $config['replicaAdapter'])))
        ;
    }

    public function addConfiguration(NodeDefinition $node)
    {
        $node
            ->children()
                ->scalarNode('sourceAdapter')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('replicaAdapter')->isRequired()->cannotBeEmpty()->end()
            ->end()
        ;
    }
}
