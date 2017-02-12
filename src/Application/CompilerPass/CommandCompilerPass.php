<?php

namespace Nanbando\Application\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class CommandCompilerPass implements CompilerPassInterface
{
    const SERVICE_ID = 'nanbando.server_registry';
    const TAG_NAME = 'nanbando.server_command';
    const SERVER_ATTRIBUTE = 'server';
    const COMMAND_ATTRIBUTE = 'command';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $references = [];
        foreach ($container->findTaggedServiceIds(self::TAG_NAME) as $id => $tags) {
            foreach ($tags as $attributes) {
                $index = $attributes[self::SERVER_ATTRIBUTE] . '::' . $attributes[self::COMMAND_ATTRIBUTE];
                $references[$index] = new Reference($id);
            }
        }

        $container->getDefinition(self::SERVICE_ID)->replaceArgument(0, $references);
    }
}
