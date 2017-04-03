<?php

namespace Nanbando\Core\Server\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Interface for server-command.
 */
interface CommandInterface
{
    /**
     * Interact with user.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return
     */
    public function interact(InputInterface $input, OutputInterface $output);

    /**
     * Executes command.
     *
     * @param array $options
     *
     * @return mixed
     */
    public function execute(array $options = []);
}
