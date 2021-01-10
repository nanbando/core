<?php

namespace Nanbando\Bundle\Command;

use Composer\Installer;
use Composer\IO\ConsoleIO;
use Dflydev\EmbeddedComposer\Core\EmbeddedComposerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

class ReconfigureCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected static $defaultName = 'reconfigure';

    protected function configure()
    {
        $this
            ->setName(self::$defaultName)
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
     * Rebuild the dependencies for symfony container.
     */
    protected function rebuild(InputInterface $input, OutputInterface $output)
    {
        /** @var EmbeddedComposerInterface $embeddedComposer */
        $embeddedComposer = $this->getApplication()->getEmbeddedComposer();

        $io = new ConsoleIO($input, $output, $this->getApplication()->getHelperSet());
        $composer = $embeddedComposer->createComposer($io);
        $rootPackage = $composer->getPackage();

        $discovery = [];
        foreach ($rootPackage->getRequires() as $require) {
            $package = $composer->getRepositoryManager()->findPackage($require->getTarget(), $require->getConstraint());
            if (!$package) {
                continue;
            }

            $bundleClasses = $package->getExtra()['nanbando']['bundle-classes'] ?? [];

            foreach ($bundleClasses as $bundleClass) {
                if ($bundleClass && class_exists($bundleClass)) {
                    $discovery[] = $bundleClass;
                }
            }
        }

        $filesystem = new Filesystem();
        $filesystem->remove(Path::join([getcwd(), NANBANDO_DIR, 'app', 'cache']));
        $filesystem->dumpFile(Path::join([getcwd(), NANBANDO_DIR, '.discovery']), \json_encode($discovery) ?? '');
    }
}
