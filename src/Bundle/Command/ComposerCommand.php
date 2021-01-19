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

class ComposerCommand extends BaseServerCommand
{
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
                        'server',
                        's',
                        InputOption::VALUE_REQUIRED,
                        'Where should the command be called.',
                        'local'
                    ),
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

    protected function getServerName(InputInterface $input)
    {
        return $input->getOption('server');
    }

    protected function getCommandOptions(InputInterface $input)
    {
        return [
            '--verbose' => $input->getOption('verbose'),
            '--prefer-source' => $input->getOption('prefer-source'),
            '--dry-run' => $input->getOption('dry-run'),
            '--dev' => $input->getOption('dev'),
            '--no-scripts' => $input->getOption('no-scripts'),
        ];
    }

    public function isEnabled()
    {
        return file_exists($this->getApplication()->getEmbeddedComposer()->getExternalComposerFilename());
    }
}
