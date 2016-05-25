<?php

namespace Nanbando\Bundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BackupCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('backup')
            ->addArgument('label', InputArgument::OPTIONAL, 'This label will be used to generate the filename for the backup.')
            ->addOption('message', 'm', InputOption::VALUE_OPTIONAL, 'An additional message to identify the backup.')
            ->setDescription('Backup data into local archive')
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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getContainer()->get('nanbando')->backup($input->getArgument('label'), $input->getOption('message'));
    }
}
