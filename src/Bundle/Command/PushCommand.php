<?php

namespace Nanbando\Bundle\Command;

use Nanbando\Core\Storage\StorageInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class PushCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected static $defaultName = 'push';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::$defaultName)
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
        $storage = $this->container->get('storage');

        foreach ($storage->localListing() as $file) {
            $storage->push($file);
        }

        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        return $this->container->has('filesystem.remote');
    }
}
