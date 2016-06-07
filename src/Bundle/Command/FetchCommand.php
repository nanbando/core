<?php

namespace Nanbando\Bundle\Command;

use Nanbando\Core\Storage\StorageInterface;
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

        /** @var StorageInterface $storage */
        $storage = $this->getContainer()->get('storage');

        $remoteFiles = $storage->remoteListing();
        $localFiles = $storage->localListing();

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
        /** @var StorageInterface $storage */
        $storage = $this->getContainer()->get('storage');

        foreach ($input->getArgument('files') as $file) {
            $storage->fetch($file);
        }
    }
}
