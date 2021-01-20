<?php

namespace Nanbando\Core\Server\Command\Ssh;

use Nanbando\Core\BackupStatus;
use Nanbando\Core\Server\Command\CommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Install/Update dependencies on a ssh connected server.
 */
class SshComposerCommand implements CommandInterface
{
    /**
     * @var SshConnection
     */
    private $connection;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var bool
     */
    private $update;

    public function __construct(SshConnection $connection, OutputInterface $output, bool $update)
    {
        $this->connection = $connection;
        $this->output = $output;
        $this->update = $update;
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
        $this->connection->executeNanbando(
            'plugins:' . ($this->update ? 'update' : 'install'),
            $options,
            function ($line) use (&$result) {
                $this->output->writeln($line);
            }
        );
    }
}
