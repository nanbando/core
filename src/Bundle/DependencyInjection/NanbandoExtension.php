<?php

namespace Nanbando\Bundle\DependencyInjection;

use League\Flysystem\FilesystemInterface;
use Nanbando\Bundle\DependencyInjection\Factory\AdapterFactoryInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Webmozart\PathUtil\Path;

/**
 * Extends container with nanbando.
 */
class NanbandoExtension extends Extension
{
    /**
     * @var AdapterFactoryInterface
     */
    private $adapterFactories;

    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('factories.xml');

        $adapterFactories = $this->getAdapterFactories($container);

        $configuration = new Configuration($adapterFactories);
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
        $container->setParameter('nanbando.servers', $config['servers']);
        $container->setParameter('nanbando.storage.local_directory', realpath($config['storage']['local_directory']));

        if (array_key_exists('remote_service', $config['storage'])
            && $config['storage']['remote_service'] !== 'filesystem.remote'
        ) {
            $container->setAlias('filesystem.remote', $config['storage']['remote_service']);
        }

        $config['adapters']['local'] = [
            'local' => [
                'directory' => $container->getParameter('nanbando.storage.local_directory'),
            ],
        ];

        $config['filesystems']['local'] = [
            'adapter' => 'local',
            'alias' => 'filesystem.local',
            'plugins' => ['filesystem.list_files'],
        ];

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');
        $loader->load('event-listener.xml');
        $loader->load('local-commands.xml');
        $loader->load('ssh-commands.xml');
        $loader->load('commands.xml');
        $loader->load('adapters.xml');
        $loader->load('flysystem.xml');
        $loader->load('plugins.xml');

        $adapters = [];

        foreach ($config['adapters'] as $name => $adapter) {
            $adapters[$name] = $this->createAdapter($name, $adapter, $container, $adapterFactories);
        }

        foreach ($config['filesystems'] as $name => $filesystem) {
            $this->createFilesystem($name, $filesystem, $container, $adapters);
        }

        // ensure container rebuild after puli bindings changes
        $discoveryFile = Path::join([getcwd(), NANBANDO_DIR, '.discovery']);
        if (file_exists($discoveryFile)) {
            $container->addResource(new FileResource($discoveryFile));
        }
    }

    private function getAdapterFactories(ContainerBuilder $container)
    {
        if (null !== $this->adapterFactories) {
            return $this->adapterFactories;
        }

        $factories = array();
        $services = $container->findTaggedServiceIds('nanbando.adapter_factory');

        foreach (array_keys($services) as $id) {
            $factory = $container->get($id);
            $factories[str_replace('-', '_', $factory->getKey())] = $factory;
        }

        return $this->adapterFactories = $factories;
    }

    private function createAdapter($name, array $config, ContainerBuilder $container, array $factories)
    {
        foreach ($config as $key => $adapter) {
            if (array_key_exists($key, $factories)) {
                $id = sprintf('nanbando.%s_adapter', $name);
                $factories[$key]->create($container, $id, $adapter);

                return $id;
            }
        }

        throw new \LogicException(sprintf('The adapter \'%s\' is not configured.', $name));
    }

    private function createFilesystem($name, array $config, ContainerBuilder $container, array $adapters)
    {
        if (!array_key_exists($config['adapter'], $adapters)) {
            throw new \LogicException(sprintf('The adapter \'%s\' is not defined.', $config['adapter']));
        }

        $adapter = $adapters[$config['adapter']];
        $id = sprintf('nanbando.%s_filesystem', $name);

        $tagParams = array('key' => $name);

        if ($config['mount'] ?? null) {
            $tagParams['mount'] = $config['mount'];
        }

        $options = [];

        if (array_key_exists('visibility', $config)) {
            $options['visibility'] = $config['visibility'];
        }

        if (array_key_exists('disable_asserts', $config)) {
            $options['disable_asserts'] = $config['disable_asserts'];
        }

        $container
            ->setDefinition($id, new ChildDefinition('nanbando.filesystem'))
            ->replaceArgument(0, new Reference($adapter))
            ->replaceArgument(1, $options)
            ->addTag('nanbando.filesystem', $tagParams)
            ->setPublic(true)
        ;

        if (!empty($config['alias'])) {
            $container->getDefinition($id)->setPublic(false);

            if (null === $alias = $container->setAlias($config['alias'], $id)) {
                $alias = $container->getAlias($config['alias']);
            }

            $alias->setPublic(true);
        }

        // Attach Plugins
        $defFilesystem = $container->getDefinition($id);

        if (isset($config['plugins']) && is_array($config['plugins'])) {
            foreach ($config['plugins'] as $pluginId) {
                $defFilesystem->addMethodCall('addPlugin', array(new Reference($pluginId)));
            }
        }

        if (method_exists($container, 'registerAliasForArgument')) {
            $aliasName = $name;

            if (!preg_match('~filesystem$~i', $aliasName)) {
                $aliasName .= 'Filesystem';
            }

            $container->registerAliasForArgument($id, FilesystemInterface::class, $aliasName)->setPublic(false);
        }
    }
}
