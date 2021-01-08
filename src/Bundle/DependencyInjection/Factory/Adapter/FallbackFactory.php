<?php

namespace Nanbando\Bundle\DependencyInjection\Factory\Adapter;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Reference;
use Nanbando\Bundle\DependencyInjection\Factory\AdapterFactoryInterface;

class FallbackFactory implements AdapterFactoryInterface
{
    public function getKey()
    {
        return 'fallback';
    }

    public function create(ContainerBuilder $container, $id, array $config)
    {
        $definition = $container
            ->setDefinition($id, new ChildDefinition('nanbando.adapter.fallback'))
            ->replaceArgument(0, new Reference(sprintf('nanbando.%s_adapter', $config['mainAdapter'])))
            ->replaceArgument(1, new Reference(sprintf('nanbando.%s_adapter', $config['fallback'])))
            ->replaceArgument(2, $config['forceCopyOnMain'])
        ;
    }

    public function addConfiguration(NodeDefinition $node)
    {
        $node
            ->children()
                ->scalarNode('mainAdapter')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('fallback')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('forceCopyOnMain')->defaultFalse()->end()
            ->end()
        ;
    }
}
