<?php

namespace Nanbando\Core\Server\Command\Ssh;

use Nanbando\Core\Server\Command\CommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Lists ssh backups.
 */
class SshListBackupsCommand implements CommandInterface
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
     * @param SshConnection $connection
     * @param OutputInterface $output
     */
    public function __construct(SshConnection $connection, OutputInterface $output)
    {
        $this->connection = $connection;
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
        $parameters = [];
        if (array_key_exists('remote', $options) && $options['remote']) {
            $parameters['-r'] = '';
        }

        $this->connection->executeNanbando(
            'list:backups',
            $parameters,
            function ($line) {
                $this->output->write($line);
            }
        );
    }
}
