<?php

namespace Nanbando\Core\Server\Command\Ssh;

use phpseclib\Crypt\RSA;
use phpseclib\Net\SSH2;

final class SshFactory
{
    public static function create(array $sshConfig)
    {
        $ssh = new SSH2($sshConfig['host'], $sshConfig['port'], $sshConfig['timeout']);

        if ($sshConfig['password']) {
            if (!$ssh->login($sshConfig['username'], $sshConfig['password'])) {
                throw new \Exception('Cannot login');
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
                throw new \Exception('Cannot login');
            }

            return $ssh;
        }

        throw new \Exception('Cannot create ssh');
    }

    private function __construct()
    {
    }
}
