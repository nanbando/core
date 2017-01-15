<?php

namespace Nanbando\Bundle\Command;

use Nanbando\Core\Nanbando;
use Nanbando\Core\Storage\StorageInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class RestoreCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        // TODO add latest option

        $this
            ->setName('restore')
            ->setDescription('Restore a backup archive.')
            ->addArgument(
                'file',
                InputArgument::OPTIONAL,
                'Defines which file should be restored (backup-name or absolute path to zip file).'
            )
            ->setHelp(
                <<<EOT
The <info>{$this->getName()}</info> restores a backup archive.

EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if ($input->getArgument('file')) {
            return;
        }

        /** @var StorageInterface $storage */
        $storage = $this->getContainer()->get('storage');
        $localFiles = $storage->localListing();

        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion('Which backup', $localFiles);
        $question->setErrorMessage('Backup %s is invalid.');
        $question->setAutocompleterValues([]);

        $input->setArgument('file', $helper->ask($input, $output, $question));
        $output->writeln('');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Nanbando $nanbando */
        $nanbando = $this->getContainer()->get('nanbando');
        $nanbando->restore($input->getArgument('file'));
    }
}
