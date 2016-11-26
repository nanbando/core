<?php

namespace Nanbando\Bundle\Command;

use Nanbando\Core\Database\ReadonlyDatabase;
use Nanbando\Core\Storage\StorageInterface;
use ScriptFUSION\Byte\ByteFormatter;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class InformationCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('information')
            ->setDescription('Fetches backup archives from remote storage.')
            ->addArgument('file', InputArgument::OPTIONAL, 'Defines which file should be used to display information.')
            ->setHelp(
                <<<EOT
The <info>{$this->getName()}</info> displays information for given backup archive.

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
        $question->setMultiselect(true);
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
        /** @var StorageInterface $storage */
        $storage = $this->getContainer()->get('storage');
        $backupFilesystem = $storage->open($input->getArgument('file'));

        $database = new ReadonlyDatabase(json_decode($backupFilesystem->read('database/system.json'), true));

        $output->writeln(sprintf(' * label:    %s', $database->get('label')));
        $output->writeln(sprintf(' * message:  %s', $database->get('message')));
        $output->writeln(sprintf(' * started:  %s', $database->get('started')));
        $output->writeln(sprintf(' * finished: %s', $database->get('finished')));
        $output->writeln(sprintf(' * size:     %s', (new ByteFormatter())->format($storage->size($backupFilesystem))));
    }
}
