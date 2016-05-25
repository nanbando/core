<?php

namespace Nanbando\Bundle\Command;

use League\Flysystem\Filesystem;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
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
        // TODO add latest option

        $this
            ->setName('fetch')
            ->setDescription('Fetches backup archives from remote storage.')
            ->addArgument('files', InputArgument::IS_ARRAY, 'Defines which file will be downloaded.')
            ->setHelp(
                <<<EOT
The <info>{$this->getName()}</info> command fetches backup archives from remote storage.

The command will ask you which archive should be downloaded if no file isset.

EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if ($input->hasArgument('files') && !empty($input->getArgument('files'))) {
            return;
        }

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

        $input->setArgument('files', $helper->ask($input, $output, $question));
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

        foreach ($input->getArgument('files') as $file) {
            $path = sprintf('%s/%s.zip', $name, $file);
            $localFilesystem->putStream($path, $remoteFilesystem->readStream($path));
        }
    }
}
