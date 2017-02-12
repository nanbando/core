<?php

namespace Nanbando\Core\Server\Command;

/**
 * Interface for server-command.
 */
interface CommandInterface
{
    /**
     * Executes command.
     *
     * @param array $options
     *
     * @return mixed
     */
    public function execute(array $options = []);
}
