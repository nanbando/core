<?php

namespace Nanbando\Bundle\DependencyInjection;

use Nanbando\Core\Server\Command\Ssh\SshFactory;
use phpseclib\Net\SSH2;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Webmozart\PathUtil\Path;

class NanbandoExtension extends Extension
{
    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $filesystem = new Filesystem();
        $filesystem->mkdir($config['storage']['local_directory']);

        $container->setParameter('nanbando.name', $config['name']);
        $container->setParameter('nanbando.environment', $config['environment']);
        $container->setParameter('nanbando.application.name', $config['application']['name']);
        $container->setParameter('nanbando.application.version', $config['application']['version']);
        $container->setParameter('nanbando.application.options', $config['application']['options']);
        $container->setParameter('nanbando.temp', $config['temp']);
        $container->setParameter('nanbando.backup', $config['backup']);
        $container->setParameter('nanbando.presets', $config['presets']);
        $container->setParameter('nanbando.storage.local_directory', realpath($config['storage']['local_directory']));

        if (array_key_exists('remote_service', $config['storage'])
            && $config['storage']['remote_service'] !== 'filesystem.remote'
        ) {
            $container->setAlias('filesystem.remote', $config['storage']['remote_service']);
        }

        $container->prependExtensionConfig(
            'oneup_flysystem',
            [
                'adapters' => [
                    'local' => [
                        'local' => [
                            'directory' => $container->getParameter('nanbando.storage.local_directory'),
                        ],
                    ],
                ],
                'filesystems' => [
                    'local' => [
                        'adapter' => 'local',
                        'alias' => 'filesystem.local',
                        'plugins' => ['filesystem.list_files'],
                    ],
                ],
            ]
        );

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');
        $loader->load('event-listener.xml');
        $loader->load('local-commands.xml');
        $loader->load('ssh-commands.xml');

        $abstractServices = $container->findTaggedServiceIds('nanbando.ssh.abstract_server_command');
        foreach ($config['servers'] as $serverName => $serverConfig) {
            $sshId = 'nanbando.server.' . $serverName . '.ssh';
            $sshDefinition = new Definition(SSH2::class, [$serverConfig['ssh']]);
            $sshDefinition->setLazy(true);
            $sshDefinition->setFactory([SshFactory::class, 'create']);
            $container->setDefinition($sshId, $sshDefinition);

            foreach ($abstractServices as $id => $tags) {
                $abstractCommandDefinition = $container->getDefinition($id);

                $commandDefinition = new DefinitionDecorator($id);
                $commandDefinition->setClass($abstractCommandDefinition->getClass());
                $commandDefinition->setLazy(true);
                $commandDefinition->replaceArgument(0, new Reference($sshId));
                $commandDefinition->replaceArgument(1, $serverConfig['directory']);
                $commandDefinition->replaceArgument(2, $serverConfig['executable']);
                $commandDefinition->addTag(
                    'nanbando.server_command',
                    ['server' => $serverName, 'command' => $tags[0]['command']]
                );

                $container->setDefinition(
                    'nanbando.server.' . $serverName . '.' . $tags[0]['command'],
                    $commandDefinition
                );
            }
        }

        // ensure container rebuild after puli bindings changes
        $puliFile = Path::join([getcwd(), NANBANDO_DIR, '.puli']);
        if (file_exists($puliFile)) {
            $container->addResource(new FileResource($puliFile));
        }
    }
}
