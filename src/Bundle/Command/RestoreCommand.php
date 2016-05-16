<?php

namespace Nanbando\Bundle\Command;

use League\Flysystem\Filesystem;
use Nanbando\Core\Nanbando;
use Nanbando\Core\Temporary\TemporaryFileManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
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
        $this->setName('restore');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Nanbando $nanbando */
        $nanbando = $this->getContainer()->get('nanbando');
        /** @var Filesystem $localFilesystem */
        $localFilesystem = $this->getContainer()->get('filesystem.local');
        /** @var TemporaryFileManager $temporaryFileManager */
        $temporaryFileManager = $this->getContainer()->get('temporary_files');

        $localFiles = array_filter(
            array_map(
            function ($item) {
                return $item['filename'];
            },
            $localFilesystem->listFiles($this->getContainer()->getParameter('nanbando.name'))
            )
        );

        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion('Which backup', $localFiles);
        $question->setErrorMessage('Backup %s is invalid.');

        $file = $helper->ask($input, $output, $question);
        $output->writeln('');

        // TODO show progressbar
        $tempFileName = $temporaryFileManager->getFilename();
        $stream = $localFilesystem->readStream(
            sprintf('%s/%s.zip', $this->getContainer()->getParameter('nanbando.name'), $file)
        );
        file_put_contents($tempFileName, $stream);
        $nanbando->restore($tempFileName);
        @unlink($tempFileName);
    }
}
