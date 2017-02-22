<?php

namespace Nanbando\Core\Server\Command\Ssh;

use Nanbando\Core\Server\Command\CommandInterface;
use Nanbando\Core\Storage\StorageInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Load a backup from a ssh connected server.
 */
class SshLoadCommand implements CommandInterface
{
    /**
     * @var SshConnection
     */
    private $connection;

    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @param SshConnection $connection
     * @param StorageInterface $storage
     * @param OutputInterface $output
     */
    public function __construct(SshConnection $connection, StorageInterface $storage, OutputInterface $output)
    {
        $this->connection = $connection;
        $this->storage = $storage;
        $this->output = $output;
    }

    /**
     * {@inheritdoc}
     */
    public function interact()
    {
        // do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $options = [])
    {
        $name = $options['name'];

        $information = $this->connection->executeNanbando('information', [$name]);
        $this->output->writeln($information);

        preg_match('/path:\s*(?<path>\/([^\/\0]+(\/)?)+)\n/', $information, $matches);
        $remotePath = $matches['path'];

        $localPath = $this->storage->path($name);
        $this->output->writeln(PHP_EOL . '$ scp ' . $remotePath . ' ' . $localPath);

        // Try to display progress somehow.
        $this->connection->get($remotePath, $localPath);

        $this->output->writeln(PHP_EOL . sprintf('Backup "%s" loaded successfully', $name));

        return $name;
    }
}
