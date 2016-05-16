<?php

namespace Nanbando\Bundle\Command;

use Humbug\SelfUpdate\Updater;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SelfUpdateCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('self-update');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $updater = new Updater();
        $updater->getStrategy()->setPharUrl('http://nanbando.github.io/core/nanbando.phar');
        $updater->getStrategy()->setVersionUrl('http://nanbando.github.io/core/nanbando.phar.version');

        $result = $updater->update();
        if (!$result) {
            $output->writeln('You are already using composer version ' . $updater->getNewVersion());
            
            // No update needed!
            return;
        }

        $new = $updater->getNewVersion();
        $old = $updater->getOldVersion();

        $output->writeln(sprintf('Updated from %s to %s', $old, $new));
    }
}
