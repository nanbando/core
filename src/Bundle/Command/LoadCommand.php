<?php

namespace Nanbando\Bundle\Command;

use Nanbando\Core\Server\ServerRegistry;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoadCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('load')
            ->addArgument('from', InputArgument::REQUIRED, '???')
            ->addArgument('name', InputArgument::REQUIRED, '???')
            ->setDescription('Backup data into local archive.')
            ->setHelp(
                <<<EOT
EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ServerRegistry $serverRegistry */
        $serverRegistry = $this->getContainer()->get('nanbando.server_registry');
        $command = $serverRegistry->getCommand($input->getArgument('from'), 'load');

        // TODO server does not provide load command

        $command->execute(['name' => $input->getArgument('name')]);
    }
}
