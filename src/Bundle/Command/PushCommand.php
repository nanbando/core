<?php

namespace Nanbando\Bundle\Command;

use League\Flysystem\Filesystem;
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
        $name = $this->getContainer()->getParameter('nanbando.name');

        /** @var Filesystem $localFilesystem */
        $localFilesystem = $this->getContainer()->get('filesystem.local');
        /** @var Filesystem $remoteFilesystem */
        $remoteFilesystem = $this->getContainer()->get('filesystem.remote');

        $localFiles = array_map(
            function ($item) {
                return $item['path'];
            },
            $localFilesystem->listFiles($name)
        );

        foreach ($localFiles as $file) {
            $remoteFilesystem->putStream($file, $localFilesystem->readStream($file));
        }
    }
}
