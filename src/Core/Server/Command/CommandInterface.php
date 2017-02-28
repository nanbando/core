<?php

namespace Nanbando\Core\Server\Command;

/**
 * Interface for server-command.
 */
interface CommandInterface
{
    /**
     * Interact with user.
     */
    public function interact();

    /**
     * Executes command.
     *
     * @param array $options
     *
     * @return mixed
     */
    public function execute(array $options = []);
}
