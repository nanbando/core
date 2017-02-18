<?php

namespace Nanbando\Application\CompilerPass;

use phpseclib\Net\SSH2;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Create ssh services for services.
 */
class SshServerCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $abstractServices = $container->findTaggedServiceIds('nanbando.ssh.abstract_server_command');
        foreach ($container->getParameter('nanbando.servers') as $serverName => $serverConfig) {
            $sshId = 'nanbando.server.' . $serverName . '.ssh';
            $container->setDefinition(
                $sshId,
                $this->createSshDefinition(
                    $serverName,
                    $serverConfig['ssh'],
                    $serverConfig['directory'],
                    $serverConfig['executable']
                )
            );

            foreach ($abstractServices as $id => $tags) {
                $commandDefinition = $container->getDefinition($id);
                $commandId = 'nanbando.server.' . $serverName . '.' . $tags[0]['command'];
                $container->setDefinition(
                    $commandId,
                    $this->createCommandDefinition(
                        $id,
                        $sshId,
                        $commandDefinition->getClass(),
                        $serverName,
                        $tags[0]['command'],
                        $serverConfig
                    )
                );
            }
        }
    }

    /**
     * Create a new ssh definition.
     *
     * @param string $serverName
     * @param array $sshConfig
     * @param string $directory
     * @param string $executable
     *
     * @return Definition
     */
    private function createSshDefinition($serverName, array $sshConfig, $directory, $executable)
    {
        $sshDefinition = new Definition(SSH2::class, [$serverName, $sshConfig, $directory, $executable]);
        $sshDefinition->setLazy(true);
        $sshDefinition->setFactory([new Reference('nanbando.ssh_factory'), 'create']);

        return $sshDefinition;
    }

    /**
     * Create a new command definition.
     *
     * @param string $id
     * @param string $sshId
     * @param string $class
     * @param string $serverName
     * @param string $command
     * @param array $serverConfig
     *
     * @return DefinitionDecorator
     */
    private function createCommandDefinition($id, $sshId, $class, $serverName, $command, array $serverConfig)
    {
        $commandDefinition = new DefinitionDecorator($id);
        $commandDefinition->setClass($class);
        $commandDefinition->setLazy(true);
        $commandDefinition->replaceArgument(0, new Reference($sshId));
        $commandDefinition->replaceArgument(1, $serverConfig['directory']);
        $commandDefinition->replaceArgument(2, $serverConfig['executable']);
        $commandDefinition->addTag('nanbando.server_command', ['server' => $serverName, 'command' => $command]);

        return $commandDefinition;
    }
}
