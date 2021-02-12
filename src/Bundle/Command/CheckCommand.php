<?php

namespace Nanbando\Bundle\Command;

use Nanbando\Core\Plugin\PluginRegistry;
use Nanbando\Core\Presets\PresetStore;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\OptionsResolver\Exception\InvalidArgumentException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Yaml\Yaml;

/**
 * This command provides functionality to check configuration.
 */
class CheckCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected static $defaultName = 'check';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(self::$defaultName)->setDescription('Checks configuration issues')->setHelp(
                <<<EOT
The <info>{$this->getName()}</info> command looks for configuration issues

EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Configuration Check Report');

        $io->writeln('Name:            ' . $this->container->getParameter('nanbando.name'));
        $io->writeln('Environment:     ' . $this->container->getParameter('nanbando.environment'));
        $io->writeln('Local directory: ' . $this->container->getParameter('nanbando.storage.local_directory'));

        if (!$this->container->has('filesystem.remote')) {
            $io->warning(
                'No remote storage configuration found. This leads into disabled "fetch" and "push" commands.'
                . 'Please follow the documentation for global configuration.'
                . PHP_EOL
                . PHP_EOL
                . 'http://nanbando.readthedocs.io/en/latest/configuration.html#global-configuration'
            );
        } else {
            $io->writeln('Remote Storage: YES');
        }

        $backups = $this->getBackups();
        if (0 === count($backups)) {
            $io->warning(
                'No backup configuration found. Please follow the documentation for local configuration.'
                . PHP_EOL
                . PHP_EOL
                . 'http://nanbando.readthedocs.io/en/latest/configuration.html#local-configuration'
            );
        }

        $this->checkBackups($io, $backups);

        $io->writeln('');

        return 0;
    }

    /**
     * Check backup-configuration.
     *
     * @param SymfonyStyle $io
     * @param array $backups
     */
    private function checkBackups(SymfonyStyle $io, array $backups)
    {
        /** @var PluginRegistry $plugins */
        $plugins = $this->container->get('plugins');
        foreach ($backups as $name => $backup) {
            $this->checkBackup($plugins, $io, $name, $backup);
        }
    }

    /**
     * Check single backup-configuration.
     *
     * @param PluginRegistry $plugins
     * @param SymfonyStyle $io
     * @param string $name
     * @param array $backup
     *
     * @return bool
     */
    private function checkBackup(PluginRegistry $plugins, SymfonyStyle $io, $name, array $backup)
    {
        $io->section('Backup: ' . $name);
        if (!$plugins->has($backup['plugin'])) {
            $io->warning(sprintf('Plugin "%s" not found', $backup['plugin']));

            return false;
        }

        $optionsResolver = new OptionsResolver();
        $plugins->getPlugin($backup['plugin'])->configureOptionsResolver($optionsResolver);

        try {
            $parameter = $optionsResolver->resolve($backup['parameter']);
        } catch (InvalidArgumentException $e) {
            $io->warning(sprintf('Parameter not valid' . PHP_EOL . PHP_EOL . 'Message: "%s"', $e->getMessage()));

            return false;
        }

        $io->write('Process: ');
        $process = 'All';
        if (0 < count($backup['process'])) {
            $process = '["' . implode('", "', $backup['process']) . '""]';
        }
        $io->writeln($process);

        $io->write('Parameter:');
        $messages = array_filter(explode("\r\n", Yaml::dump($parameter)));
        $io->block($messages, null, null, '  ');

        $io->writeln('OK');

        return true;
    }

    /**
     * Returns backup configuration and merge it with preset if exists.
     *
     * @return array
     */
    private function getBackups()
    {
        $backups = $this->container->getParameter('nanbando.backup');

        $preset = [];
        if ($name = $this->getParameter('nanbando.application.name')) {
            /** @var PresetStore $presetStore */
            $presetStore = $this->container->get('presets');
            $preset = $presetStore->getPreset(
                $name,
                $this->getParameter('nanbando.application.version'),
                $this->getParameter('nanbando.application.options')
            );
        }

        return array_merge($preset, $backups);
    }

    /**
     * Returns container parameter or default value.
     *
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function getParameter($name, $default = null)
    {
        if (!$this->container->hasParameter($name)) {
            return $default;
        }

        return $this->container->getParameter($name);
    }
}
