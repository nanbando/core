<?php

namespace Nanbando\Bundle\Command;

use Composer\Installer;
use Composer\IO\ConsoleIO;
use Dflydev\EmbeddedComposer\Core\EmbeddedComposerInterface;
use Puli\Discovery\Api\Type\BindingType;
use Puli\Manager\Api\Discovery\BindingTypeDescriptor;
use Puli\Manager\Api\Puli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

class ReconfigureCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('reconfigure')
            ->setDescription('Reconfigure application')
            ->setDefinition(
                [
                    new InputOption(
                        'prefer-source',
                        null,
                        InputOption::VALUE_NONE,
                        'Forces installation from package sources when possible, including VCS information.'
                    ),
                    new InputOption(
                        'dry-run',
                        null,
                        InputOption::VALUE_NONE,
                        'Outputs the operations but will not execute anything (implicitly enables --verbose).'
                    ),
                    new InputOption(
                        'dev',
                        null,
                        InputOption::VALUE_NONE,
                        'Enables installation of dev-require packages.'
                    ),
                    new InputOption(
                        'no-scripts',
                        null,
                        InputOption::VALUE_NONE,
                        'Skips the execution of all scripts defined in nanbando.json file.'
                    ),
                    new InputOption(
                        'update',
                        null,
                        InputOption::VALUE_NONE,
                        'Updated dependencies and lock-file.'
                    ),
                ]
            )
            ->setHelp(
                <<<EOT
The <info>{$this->getName()}</info> command reads a nanbando.json formatted file.
The file is read from the current directory.

It installs the dependencies and reconfigures the local application.

EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $installer = $this->update($input, $output);
        $this->rebuild($input, $output);

        return $installer;
    }

    protected function update(InputInterface $input, OutputInterface $output)
    {
        /** @var EmbeddedComposerInterface $embeddedComposer */
        $embeddedComposer = $this->getApplication()->getEmbeddedComposer();

        $io = new ConsoleIO($input, $output, $this->getApplication()->getHelperSet());
        /** @var Installer $installer */
        $installer = $embeddedComposer->createInstaller($io);

        $installer
            ->setDryRun($input->getOption('dry-run'))
            ->setVerbose($input->getOption('verbose'))
            ->setPreferSource($input->getOption('prefer-source'))
            ->setDevMode($input->getOption('dev'))
            ->setRunScripts(!$input->getOption('no-scripts'))
            ->setUpdate($input->getOption('update'));

        return $installer->run();
    }

    /**
     * Rebuild the puli dependencies for symfony container.
     */
    protected function rebuild(InputInterface $input, OutputInterface $output)
    {
        $puli = new Puli(Path::join([getcwd(), NANBANDO_DIR]));
        $puli->start();

        /** @var EmbeddedComposerInterface $embeddedComposer */
        $embeddedComposer = $this->getApplication()->getEmbeddedComposer();

        $packageManager = $puli->getPackageManager();
        $io = new ConsoleIO($input, $output, $this->getApplication()->getHelperSet());
        $composer = $embeddedComposer->createComposer($io);
        $installationManager = $composer->getInstallationManager();
        $rootPackage = $composer->getPackage();

        $repository = $composer->getRepositoryManager()->getLocalRepository();
        $packages = [];
        foreach ($repository->getPackages() as $package) {
            $packages[$package->getName()] = $package;
        }

        foreach ($rootPackage->getRequires() as $require) {
            if (!array_key_exists($require->getTarget(), $packages)) {
                continue;
            }

            $packageManager->installPackage(
                Path::normalize($installationManager->getInstallPath($packages[$require->getTarget()])),
                $require->getTarget(),
                'nanbando'
            );
        }

        $filesystem = new Filesystem();
        $filesystem->remove(Path::join([getcwd(), NANBANDO_DIR, '.puli']));

        $discoveryManager = $puli->getDiscoveryManager();
        if (!$discoveryManager->hasRootTypeDescriptor('nanbando/bundle')) {
            $discoveryManager->addRootTypeDescriptor(new BindingTypeDescriptor(new BindingType('nanbando/bundle')), 0);
        }

        $discoveryManager->clearDiscovery();
        $discoveryManager->buildDiscovery();

        $filesystem = new Filesystem();
        $filesystem->remove(Path::join([getcwd(), NANBANDO_DIR, 'app', 'cache']));
    }
}
