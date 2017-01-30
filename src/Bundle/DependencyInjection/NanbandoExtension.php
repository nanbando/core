<?php

namespace Nanbando\Bundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

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

        // ensure container rebuild after puli bindings changes
        if (file_exists('.puli/bindings.json')) {
            $container->addResource(new FileResource('.puli/bindings.json'));
        }
    }
}
