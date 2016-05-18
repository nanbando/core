<?php

namespace Nanbando\Bundle\Command;

use Dflydev\EmbeddedComposer\Console\Command\UpdateCommand as ComposerUpdateCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCommand extends ComposerUpdateCommand
{
    public function __construct()
    {
        parent::__construct('');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $installer = parent::execute($input, $output);

        $command = $this->getApplication()->find('rebuild');
        $command->run($input, $output);

        return $installer;
    }
}
