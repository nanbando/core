<?php

namespace Nanbando\Core\Server\Command\Ssh;

use phpseclib\Crypt\RSA;
use phpseclib\Net\SCP;
use phpseclib\Net\SSH2;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * SSH connection wrapper.
 *
 * Handles login and execute commands.
 */
class SshConnection
{
    const TEST_COMMAND = <<<'EOT'
%s
if [ %s ]; then
  echo "exists"
fi
EOT;

    /**
     * @var SSH2
     */
    private $ssh;

    /**
     * @var SCP
     */
    private $scp;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $directory;

    /**
     * @var string
     */
    private $executable;

    /**
     * @var array
     */
    private $sshConfig;

    /**
     * @var bool
     */
    private $loggedIn = false;

    /**
     * @param SSH2 $ssh
     * @param SCP $scp
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $name
     * @param string $directory
     * @param string $executable
     * @param array $sshConfig
     */
    public function __construct(
        SSH2 $ssh,
        SCP $scp,
        InputInterface $input,
        OutputInterface $output,
        $name,
        $directory,
        $executable,
        array $sshConfig
    ) {
        $this->ssh = $ssh;
        $this->scp = $scp;
        $this->input = $input;
        $this->output = $output;
        $this->name = $name;
        $this->directory = $directory;
        $this->executable = $executable;
        $this->sshConfig = $sshConfig;
    }

    /**
     * Executes command on connected server.
     *
     * @param string $command
     * @param callable|null $callback
     *
     * @return string
     */
    public function execute($command, callable $callback = null)
    {
        $this->login();

        return $this->ssh->exec(sprintf('cd %s; %s', $this->directory, $command), $callback);
    }

    /**
     * Executes nanbando command on connected server.
     *
     * @param $command
     * @param array $parameter
     * @param callable|null $callback
     *
     * @return string
     */
    public function executeNanbando($command, array $parameter, callable $callback = null)
    {
        $this->login();

        $parameterString = [];
        foreach ($parameter as $key => $value) {
            $parameterString[] = (is_string($key) ? $key . ' ' : '') . $value;
        }

        return $this->execute(
            sprintf('%s %s %s', $this->executable, $command, implode(' ', $parameterString)),
            $callback
        );
    }

    /**
     * Download remote-file and save it to given local-file.
     *
     * @param string $remoteFile
     * @param string $localFile
     *
     * @return bool
     */
    public function get($remoteFile, $localFile)
    {
        $this->login();

        return $this->scp->get($remoteFile, $localFile);
    }

    /**
     * Executes login to server.
     */
    private function login()
    {
        if ($this->loggedIn) {
            return;
        }

        if (array_key_exists('password', $this->sshConfig)) {
            $this->loginWithPassword();
        }

        if (array_key_exists('rsakey', $this->sshConfig)) {
            $this->loginWithRsaKey();
        }

        $this->loggedIn = $this->validate();
    }

    /**
     * Login to server using username and password.
     *
     * @return bool
     *
     * @throws SshLoginException
     */
    private function loginWithPassword()
    {
        $password = $this->sshConfig['password'];
        if (!is_string($password)) {
            $password = $this->askForPassword(
                sprintf(
                    'Password for %s@%s:%s: ',
                    $this->sshConfig['username'],
                    $this->sshConfig['host'],
                    $this->sshConfig['port']
                )
            );
        }

        if (!$this->ssh->login($this->sshConfig['username'], $password)) {
            throw new SshLoginException($this->name);
        }

        return true;
    }

    /**
     * Login to server using rsa key-file.
     *
     * @return bool
     *
     * @throws SshLoginException
     */
    private function loginWithRsaKey()
    {
        $key = new RSA();
        $password = $this->sshConfig['rsakey']['password'];
        if ($password) {
            if (!is_string($password)) {
                $password = $this->askForPassword(
                    sprintf('Password for file "%s": ', $this->sshConfig['rsakey']['file'])
                );
            }

            $key->setPassword($password);
        }

        $key->loadKey(file_get_contents($this->sshConfig['rsakey']['file']));
        if (!$this->ssh->login($this->sshConfig['username'], $key)) {
            throw new SshLoginException($this->name);
        }

        return true;
    }

    /**
     * Uses question helper to ask for password.
     *
     * @param string $question
     *
     * @return string
     */
    private function askForPassword($question)
    {
        $questionHelper = new QuestionHelper();

        $question = new Question($question);
        $question->setHidden(true);
        $question->setHiddenFallback(false);

        $password = $questionHelper->ask($this->input, $this->output, $question);
        $this->output->writeln('');

        return $password;
    }

    /**
     * Validates ssh connection.
     *
     * @return bool
     *
     * @throws SshValidateException
     */
    private function validate()
    {
        if (!$this->testServer($this->ssh, sprintf('-d "%s"', $this->directory))) {
            throw new SshValidateException('Directory does not exists');
        }

        if (!$this->testServer($this->ssh, sprintf('-x "%s"', $this->executable), $this->directory)) {
            throw new SshValidateException('Executable does not exists');
        }

        return true;
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
}
