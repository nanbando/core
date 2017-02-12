<?php

namespace Nanbando\Core\Server\Command\Ssh;

use Nanbando\Core\Server\Command\CommandInterface;
use Nanbando\Core\Storage\StorageInterface;
use phpseclib\Net\SCP;
use phpseclib\Net\SSH2;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Load a backup from a ssh connected server.
 */
class SshLoadCommand implements CommandInterface
{
    /**
     * @var SSH2
     */
    private $ssh;

    /**
     * @var SCP
     */
    private $scp;

    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var string
     */
    private $folder;

    /**
     * @var string
     */
    private $executable = 'nanbando';

    /**
     * @param SSH2 $ssh
     * @param StorageInterface $storage
     * @param OutputInterface $output
     * @param string $folder
     * @param string $executable
     */
    public function __construct(SSH2 $ssh, $folder, $executable, StorageInterface $storage, OutputInterface $output)
    {
        $this->ssh = $ssh;
        $this->scp = new SCP($ssh);
        $this->folder = $folder;
        $this->executable = $executable;
        $this->storage = $storage;
        $this->output = $output;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $options)
    {
        $name = $options['name'];

        $information = $this->ssh->exec(sprintf('cd %s; %s information %s', $this->folder, $this->executable, $name));
        $this->output->writeln($information);

        preg_match('/path:\s*(?<path>\/([^\/\0]+(\/)?)+)\n/', $information, $matches);
        $remotePath = $matches['path'];

        $localPath = $this->storage->path($name);
        $this->output->writeln(PHP_EOL . '$ scp ' . $remotePath . ' ' . $localPath);

        // Try to display progress somehow.
        $this->scp->get($remotePath, $localPath);

        $this->output->writeln(PHP_EOL . sprintf('Backup "%s" loaded successfully', $name));
    }
}
