<?php

namespace Nanbando\Bundle\Command;

use Dflydev\EmbeddedComposer\Console\Command\InstallCommand as ComposerInstallCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InstallCommand extends ComposerInstallCommand
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
