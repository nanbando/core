<?php

namespace Nanbando\Core\Server\Command\Ssh;

use Nanbando\Core\BackupStatus;
use Nanbando\Core\Server\Command\CommandInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Create a new backup on a ssh connected server.
 */
class SshBackupCommand implements CommandInterface
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
    public function interact()
    {
        // do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $options = [])
    {
        $parameters = [$options['label']];
        if (array_key_exists('message', $options) && !empty($options['message'])) {
            $parameters['-m'] = '"' . $options['message'] . '"';
        }
        if (array_key_exists('process', $options)) {
            $parameters['-p'] = $options['process'];
        }

        $result = '';
        $this->connection->executeNanbando(
            'backup',
            $parameters,
            function ($line) use (&$result) {
                $this->output->write($line);

                $result .= $line;
            }
        );

        $match = preg_match('/"(?<name>[0-9-]*)".*(?<status>(successfully|failed|partially)).*/', $result, $matches);
        if (!$match) {
            return BackupStatus::STATE_FAILED;
        }

        if ($matches['status'] === 'failed') {
            return BackupStatus::STATE_FAILED;
        } elseif ($matches['status'] === 'partially') {
            return BackupStatus::STATE_PARTIALLY;
        }

        return BackupStatus::STATE_SUCCESS;
    }
}
