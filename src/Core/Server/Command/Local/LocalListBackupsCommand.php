<?php

namespace Nanbando\Core\Server\Command\Local;

use Nanbando\Core\Server\Command\CommandInterface;
use Nanbando\Core\Storage\StorageInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Lists local backups.
 */
class LocalListBackupsCommand implements CommandInterface
{
    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @param StorageInterface $storage
     * @param OutputInterface $output
     */
    public function __construct(StorageInterface $storage, OutputInterface $output)
    {
        $this->storage = $storage;
        $this->output = $output;
    }

    /**
     * {@inheritdoc}
     */
    public function interact(InputInterface $input, OutputInterface $output)
    {
        // do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $options = [])
    {
        $remote = array_key_exists('remote', $options) ? $options['remote'] : false;
        $files = $this->getFiles($remote);

        foreach ($files as $file) {
            $this->output->writeln($file);
        }
    }

    /**
     * Returns local of remote files.
     *
     * @param bool $remote
     *
     * @return string[]
     */
    private function getFiles($remote)
    {
        if ($remote) {
            return $this->storage->remoteListing();
        }

        return $this->storage->localListing();
    }
}
