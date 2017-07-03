<?php

namespace Nanbando\Bundle\DependencyInjection\Factory;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * TODO add description here
 */
interface AdapterFactoryInterface
{
    public function getKey();
    public function create(ContainerBuilder $container, $id, array $config);
    public function addConfiguration(NodeDefinition $builder);
}
