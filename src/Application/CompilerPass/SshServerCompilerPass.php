<?php

namespace Nanbando\Application\CompilerPass;

use Nanbando\Core\Server\Command\Ssh\SshConnection;
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
            $container->setDefinition($sshId, $this->createSshDefinition($serverConfig['ssh']));

            $connectionId = 'nanbando.server.' . $serverName . '.connection';
            $container->setDefinition(
                $connectionId,
                $this->createSshConnectionDefinition(
                    $sshId,
                    $serverName,
                    $serverConfig['directory'],
                    $serverConfig['executable'],
                    $serverConfig['ssh']
                )
            );

            foreach ($abstractServices as $id => $tags) {
                $commandDefinition = $container->getDefinition($id);
                $commandId = 'nanbando.server.' . $serverName . '.' . $tags[0]['command'];
                $container->setDefinition(
                    $commandId,
                    $this->createCommandDefinition(
                        $id,
                        $connectionId,
                        $commandDefinition->getClass(),
                        $serverName,
                        $tags[0]['command']
                    )
                );
            }
        }
    }

    /**
     * Create a new ssh definition.
     *
     * @param array $sshConfig
     *
     * @return Definition
     */
    private function createSshDefinition(array $sshConfig)
    {
        $sshDefinition = new Definition(SSH2::class, [$sshConfig['host'], $sshConfig['port'], $sshConfig['timeout']]);

        return $sshDefinition;
    }

    /**
     * Create a new ssh-connection.
     *
     * @param string $sshId
     * @param string $serverName
     * @param string $directory
     * @param string $executable
     * @param array $sshConfig
     *
     * @return Definition
     */
    private function createSshConnectionDefinition(
        $sshId,
        $serverName,
        $directory,
        $executable,
        array $sshConfig
    ) {
        $connectionDefinition = new Definition(
            SshConnection::class, [
                new Reference($sshId),
                new Reference('input'),
                new Reference('output'),
                $serverName,
                $directory,
                $executable,
                $sshConfig,
            ]
        );
        $connectionDefinition->setLazy(true);

        return $connectionDefinition;
    }

    /**
     * Create a new command definition.
     *
     * @param string $id
     * @param string $connectionId
     * @param string $class
     * @param string $serverName
     * @param string $command
     *
     * @return DefinitionDecorator
     */
    private function createCommandDefinition($id, $connectionId, $class, $serverName, $command)
    {
        $commandDefinition = new DefinitionDecorator($id);
        $commandDefinition->setClass($class);
        $commandDefinition->setLazy(true);
        $commandDefinition->replaceArgument(0, new Reference($connectionId));
        $commandDefinition->addTag('nanbando.server_command', ['server' => $serverName, 'command' => $command]);

        return $commandDefinition;
    }
}
