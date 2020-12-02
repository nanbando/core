<?php

namespace Nanbando\Bundle\Command;

use Nanbando\Core\Storage\StorageInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class FetchCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected static $defaultName = 'fetch';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::$defaultName)
            ->setDescription('Fetches backup archives from remote storage.')
            ->addArgument('files', InputArgument::IS_ARRAY, 'Defines which file will be downloaded.')
            ->addOption('latest', null, InputOption::VALUE_NONE, 'Loads the latest file.')
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
        /** @var StorageInterface $storage */
        $storage = $this->container->get('storage');
        $files = $input->getArgument('files');

        if ($input->getOption('latest')) {
            $remoteFiles = $storage->remoteListing();

            if (count($remoteFiles) > 0) {
                $files[] = end($remoteFiles);
                $input->setArgument('files', $files);
            }
        }

        if ($input->hasArgument('files') && !empty($files)) {
            return;
        }

        $remoteFiles = $storage->remoteListing();
        $localFiles = $storage->localListing();

        if (count(array_diff($remoteFiles, $localFiles)) === 0) {
            $output->writeln('All files fetched');

            return;
        }

        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Which backup', array_values(array_diff($remoteFiles, $localFiles))
        );
        $question->setMultiselect(true);
        $question->setErrorMessage('Backup %s is invalid.');
        $question->setAutocompleterValues([]);

        $input->setArgument('files', $helper->ask($input, $output, $question));
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var StorageInterface $storage */
        $storage = $this->container->get('storage');

        foreach ($input->getArgument('files') as $file) {
            $storage->fetch($file);
        }

        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        return $this->container->has('filesystem.remote');
    }
}
