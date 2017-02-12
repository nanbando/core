<?php

namespace Nanbando\Core\Server\Command\Ssh;

use phpseclib\Crypt\RSA;
use phpseclib\Net\SSH2;

/**
 * Factory for ssh connections.
 */
final class SshFactory
{
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
    public static function create($serverName, array $sshConfig)
    {
        $ssh = new SSH2($sshConfig['host'], $sshConfig['port'], $sshConfig['timeout']);

        if ($sshConfig['password']) {
            if (!$ssh->login($sshConfig['username'], $sshConfig['password'])) {
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
     * Factory class should not be constructed.
     */
    private function __construct()
    {
    }
}
