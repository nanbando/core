<?php

namespace Nanbando\Core\Server\Command\Ssh;

use Nanbando\Core\BackupStatus;
use Nanbando\Core\Server\Command\CommandInterface;
use phpseclib\Net\SSH2;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Create a new backup on a ssh connected server.
 */
class SshBackupCommand implements CommandInterface
{
    /**
     * @var SSH2
     */
    private $ssh;

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
     * @param string $folder
     * @param string $executable
     * @param OutputInterface $output
     */
    public function __construct(SSH2 $ssh, $folder, $executable, OutputInterface $output)
    {
        $this->ssh = $ssh;
        $this->folder = $folder;
        $this->executable = $executable;
        $this->output = $output;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $options = [])
    {
        $result = '';
        $this->ssh->exec(
            sprintf('cd %s; %s backup %s %s', $this->folder, $this->executable, $options['label'], $options['message']),
            function ($line) use (&$result) {
                $this->output->write($line);

                $result .= $line;
            }
        );

        $match = preg_match('/"(?<name>[0-9-]*)".*(?<status>(successfully|failed|partially)*).*/', $result, $matches);
        if ($match) {
            return BackupStatus::STATE_FAILED;
        }

        if ($matches['status'] === 'failed') {
            return BackupStatus::STATE_SUCCESS;
        } elseif ($matches['status'] === 'partially') {
            return BackupStatus::STATE_SUCCESS;
        }

        return BackupStatus::STATE_SUCCESS;
    }
}
