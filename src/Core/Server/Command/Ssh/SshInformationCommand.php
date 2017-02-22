<?php

namespace Nanbando\Core\Server\Command\Ssh;

use Nanbando\Core\Server\Command\CommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Executes information command ssh connected server.
 */
class SshInformationCommand implements CommandInterface
{
    /**
     * @var SshConnection
     */
    private $connection;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @param SshConnection $connection
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function __construct(SshConnection $connection, InputInterface $input, OutputInterface $output)
    {
        $this->connection = $connection;
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * {@inheritdoc}
     */
    public function interact()
    {
        // TODO implement interact for ssh connections.
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $options = [])
    {
        $this->connection->executeNanbando(
            'information',
            [$options['file']],
            function ($line) {
                $this->output->write($line);
            }
        );
    }
}
