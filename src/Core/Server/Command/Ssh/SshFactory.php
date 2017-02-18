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
    const TEST_COMMAND = <<<'EOT'
%s
if [ %s ]; then
  echo "exists"
fi
EOT;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var array
     */
    private $sshs = [];

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
     * @param string $serverName
     * @param array $sshConfig
     * @param string $directory
     * @param string $executable
     *
     * @return SSH2
     *
     * @throws SshException
     */
    public function create($serverName, array $sshConfig, $directory, $executable)
    {
        if (array_key_exists($serverName, $this->sshs)) {
            if ($this->sshs[$serverName] instanceof SshException) {
                throw $this->sshs[$serverName];
            }

            return $this->sshs[$serverName];
        }

        try {
            return $this->sshs[$serverName] = $this->doCreate($serverName, $sshConfig, $directory, $executable);
        } catch (SshException $ex) {
            $this->sshs[$serverName] = $ex;

            throw $ex;
        }
    }

    private function doCreate($serverName, array $sshConfig, $directory, $executable)
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

            $this->validateSsh($ssh, $directory, $executable);

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

            $this->validateSsh($ssh, $directory, $executable);

            return $ssh;
        }

        throw new SshConfigurationException($serverName);
    }

    /**
     * Validates ssh connection.
     *
     * @param SSH2 $ssh
     * @param string $directory
     * @param string $executable
     *
     * @throws SshValidateException
     */
    private function validateSsh(SSH2 $ssh, $directory, $executable)
    {
        if (!$this->testServer($ssh, sprintf('-d "%s"', $directory))) {
            throw new SshValidateException('Directory does not exists');
        }

        if (!$this->testServer($ssh, sprintf('-x "%s"', $executable), $directory)) {
            throw new SshValidateException('Executable does not exists');
        }
    }

    /**
     * Executes an if-statement on the server.
     *
     * @param SSH2 $ssh
     * @param string $test
     * @param string $directory
     *
     * @return bool
     */
    private function testServer(SSH2 $ssh, $test, $directory = null)
    {
        $result = $ssh->exec(sprintf(self::TEST_COMMAND, $directory ? 'cd ' . $directory : '', $test));

        return 1 === preg_match('/exists.*/', $result);
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

        $question = new Question('Password for ' . $username . '@' . $host . ':' . $port . ': ');
        $question->setHidden(true);
        $question->setHiddenFallback(false);

        $password = $questionHelper->ask($this->input, $this->output, $question);
        $this->output->writeln('');

        return $password;
    }
}
