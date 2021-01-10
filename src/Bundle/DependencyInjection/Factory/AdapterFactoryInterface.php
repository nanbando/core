<?php

namespace Nanbando\Bundle\DependencyInjection\Factory;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

interface AdapterFactoryInterface
{
    public function getKey(): string;

    public function create(ContainerBuilder $container, $id, array $config): void;

    public function addConfiguration(NodeDefinition $builder): void;
}
