<?php

namespace Nanbando\Bundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class NanbandoExtension extends Extension implements PrependExtensionInterface
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

        $container->setParameter('nanbando.name', $config['name']);
        $container->setParameter('nanbando.temp', $config['temp']);
        $container->setParameter('nanbando.backup', $config['backup']);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');
    }

    /**
     * {@inheritdoc}
     */
    public function prepend(ContainerBuilder $container)
    {
        if ($container->hasExtension('oneup_flysystem')) {
            $container->prependExtensionConfig(
                'oneup_flysystem',
                [
                    'adapters' => [
                        'local' => ['local' => ['directory' => realpath('.')]],
                    ],
                    'filesystems' => [
                        'local' => ['adapter' => 'local', 'alias' => 'filesystem.local'],
                    ],
                ]
            );
        }
    }
}
