<?php

namespace Nanbando\Bundle\Command;

use League\Flysystem\Filesystem;
use League\Flysystem\ZipArchive\ZipArchiveAdapter;
use Nanbando\Core\Database\ReadonlyDatabase;
use ScriptFUSION\Byte\ByteFormatter;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class InfoCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('info');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $this->getContainer()->getParameter('nanbando.name');

        /** @var Filesystem $localFilesystem */
        $localFilesystem = $this->getContainer()->get('filesystem.local');

        $localFiles = array_filter(
            array_map(
                function ($item) {
                    return $item['filename'];
                },
                $localFilesystem->listFiles($name)
            )
        );

        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion('Which backup', $localFiles);
        $question->setErrorMessage('Backup %s is invalid.');

        $file = $helper->ask($input, $output, $question);
        $output->writeln('');

        $zipFilename = sprintf(
            '%s/%s/%s.zip',
            $this->getContainer()->getParameter('nanbando.storage.locale_directory'),
            $name,
            $file
        );
        $backupFilesystem = new Filesystem(new ZipArchiveAdapter($zipFilename));

        $database = new ReadonlyDatabase(json_decode($backupFilesystem->read('/database/system.json'), true));

        $output->writeln(sprintf(' * label:    %s', $database->get('label')));
        $output->writeln(sprintf(' * message:  %s', $database->get('message')));
        $output->writeln(sprintf(' * started:  %s', $database->get('started')));
        $output->writeln(sprintf(' * finished: %s', $database->get('finished')));
        $output->writeln(sprintf(' * size:     %s', (new ByteFormatter())->format(filesize($zipFilename))));
    }
}
