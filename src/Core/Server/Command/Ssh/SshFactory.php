<?php

namespace Nanbando\Core\Server\Command\Ssh;

use phpseclib\Crypt\RSA;
use phpseclib\Net\SSH2;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Factory for ssh connections.
 */
final class SshFactory
{
    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * Create a new ssh connection.
     *
     * @param $serverName
     * @param array $sshConfig
     *
     * @return SSH2
     *
     * @throws SshConfigurationException
     * @throws SshLoginException
     */
    public function create($serverName, array $sshConfig)
    {
        $ssh = new SSH2($sshConfig['host'], $sshConfig['port'], $sshConfig['timeout']);

        if ($sshConfig['password']) {
            $password = $sshConfig['password'];
            if ($password === true) {
                $password = $this->askForPassword($sshConfig['username'], $sshConfig['host'], $sshConfig['port']);
            }

            if (!$ssh->login($sshConfig['username'], $password)) {
                throw new SshLoginException($serverName);
            }

            return $ssh;
        }

        if ($sshConfig['rsakey']) {
            $key = new RSA();
            if ($sshConfig['rsakey']['password']) {
                $key->setPassword($sshConfig['rsakey']['password']);
            }
            $key->loadKey(file_get_contents($sshConfig['rsakey']['file']));

            if (!$ssh->login($sshConfig['username'], $key)) {
                throw new SshLoginException($serverName);
            }

            return $ssh;
        }

        throw new SshConfigurationException($serverName);
    }

    /**
     * Uses question helper to ask for password.
     *
     * @param string $username
     * @param string $host
     * @param string $port
     *
     * @return string
     */
    private function askForPassword($username, $host, $port)
    {
        $questionHelper = new QuestionHelper();

        $question = new Question('Password for ' . $username . '@' . $host . ':' . $port.': ');
        $question->setHidden(true);
        $question->setHiddenFallback(false);

        $password = $questionHelper->ask($this->input, $this->output, $question);
        $this->output->writeln('');

        return $password;
    }
}
