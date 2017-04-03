<?php

namespace Nanbando\Bundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Command creates a new backup.
 */
class BackupCommand extends BaseServerCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('backup')
            ->addArgument('label', InputArgument::OPTIONAL, 'This label will be used to generate the filename for the backup.')
            ->addOption('message', 'm', InputOption::VALUE_REQUIRED, 'An additional message to identify the backup.')
            ->addOption('server', 's', InputOption::VALUE_REQUIRED, 'Where should the command be called.', 'local')
            ->addOption('process', 'p', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Defines process names which will be executed.')
            ->setDescription('Backup data into local archive.')
            ->setHelp(
                <<<EOT
The <info>{$this->getName()}</info> command reads a nanbando.json formatted file 
and runs the defined steps to backup this project.

For additional information, which should be stored in the backup archive use
the label and description option.

EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function getServerName(InputInterface $input)
    {
        return $input->getOption('server');
    }

    /**
     * {@inheritdoc}
     */
    protected function getCommandOptions(InputInterface $input)
    {
        return [
            'label' => $input->getArgument('label'),
            'message' => $input->getOption('message'),
            'process' => $input->getOption('process'),
        ];
    }
}
