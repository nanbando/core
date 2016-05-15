<?php

namespace Nanbando\Bundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
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
            ->addOption('label', 'l', InputOption::VALUE_OPTIONAL)
            ->addOption('message', 'm', InputOption::VALUE_OPTIONAL);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getContainer()->get('nanbando')->backup($input->getOption('label'), $input->getOption('message'));
    }
}
