<?php

namespace Nanbando\Bundle\Command;

use League\Flysystem\Filesystem;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class FetchCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('fetch');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $this->getContainer()->getParameter('nanbando.name');

        /** @var Filesystem $localFilesystem */
        $localFilesystem = $this->getContainer()->get('filesystem.local');
        /** @var Filesystem $remoteFilesystem */
        $remoteFilesystem = $this->getContainer()->get('filesystem.remote');

        $remoteFiles = array_map(
            function ($item) {
                return $item['filename'];
            },
            $remoteFilesystem->listFiles($name)
        );
        $localFiles = array_map(
            function ($item) {
                return $item['filename'];
            },
            $localFilesystem->listFiles($name)
        );

        if (count(array_diff($remoteFiles, $localFiles)) === 0) {
            $output->writeln('All files fetched');

            return;
        }

        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Which backup', array_diff($remoteFiles, $localFiles)
        );
        $question->setMultiselect(true);
        $question->setErrorMessage('Backup %s is invalid.');

        $files = $helper->ask($input, $output, $question);

        foreach ($files as $file) {
            $path = sprintf('%s/%s.zip', $name, $file);
            $localFilesystem->putStream($path, $remoteFilesystem->readStream($path));
        }
    }
}
