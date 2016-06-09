<?php

namespace Nanbando\Bundle\Command;

use Nanbando\Core\Storage\StorageInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PushCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('push')
            ->setDescription('Pushes all backup archives to the remote storage.')
            ->setHelp(
                <<<EOT
The <info>{$this->getName()}</info> pushes all backup archives to the remote storage.

EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var StorageInterface $storage */
        $storage = $this->getContainer()->get('storage');

        foreach ($storage->localListing() as $file) {
            $storage->push($file);
        }
    }
}
