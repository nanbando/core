<?php

namespace Nanbando\Bundle\Command;

use Composer\IO\ConsoleIO;
use Dflydev\EmbeddedComposer\Core\EmbeddedComposerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class ComposerCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var bool
     */
    private $update;

    public function __construct(bool $update)
    {
        parent::__construct($update ? 'plugins:update' : 'plugins:install');

        $this->update = $update;
    }

    protected function configure()
    {
        $this
            ->setName($this->update ? 'plugins:update' : 'plugins:install')
            ->setDescription('Install application dependencies')
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
        /** @var EmbeddedComposerInterface $embeddedComposer */
        $embeddedComposer = $this->getApplication()->getEmbeddedComposer();

        $io = new ConsoleIO($input, $output, $this->getApplication()->getHelperSet());
        $composer = $embeddedComposer->createComposer($io);
        $package = $composer->getPackage();
        $package->setStabilityFlags(array_merge($package->getStabilityFlags(),[
            'nanbando/core' => '20',
            'dflydev/embedded-composer' => '20',
        ]));

        $installer = $embeddedComposer->createInstaller($io, $composer);
        $installer
            ->setDryRun($input->getOption('dry-run'))
            ->setVerbose($input->getOption('verbose'))
            ->setPreferSource($input->getOption('prefer-source'))
            ->setDevMode($input->getOption('dev'))
            ->setRunScripts(!$input->getOption('no-scripts'))
            ->setUpdate($this->update);

        return $installer->run();
    }

    public function isEnabled()
    {
        return file_exists($this->getApplication()->getEmbeddedComposer()->getExternalComposerFilename());
    }
}
