<?php

namespace Nanbando\Core\Server\Command\Local;

use Nanbando\Core\Database\DatabaseFactory;
use Nanbando\Core\Server\Command\CommandInterface;
use Nanbando\Core\Storage\StorageInterface;
use ScriptFUSION\Byte\ByteFormatter;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * Display the information for a given backup.
 */
class LocalInformationCommand implements CommandInterface
{
    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var DatabaseFactory
     */
    private $databaseFactory;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @param StorageInterface $storage
     * @param DatabaseFactory $databaseFactory
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function __construct(
        StorageInterface $storage,
        DatabaseFactory $databaseFactory,
        InputInterface $input,
        OutputInterface $output
    ) {
        $this->storage = $storage;
        $this->databaseFactory = $databaseFactory;
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * {@inheritdoc}
     */
    public function interact()
    {
        if ($this->input->getArgument('file')) {
            return;
        }

        $localFiles = $this->storage->localListing();
        if (empty($localFiles)) {
            throw new \Exception('No local backup available.');
        }

        if ($this->input->getOption('latest')) {
            return $this->input->setArgument('file', end($localFiles));
        }

        $helper = new QuestionHelper();
        $question = new ChoiceQuestion('Which backup', $localFiles);
        $question->setErrorMessage('Backup %s is invalid.');
        $question->setAutocompleterValues([]);

        $this->input->setArgument('file', $helper->ask($this->input, $this->output, $question));
        $this->output->writeln('');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $options = [])
    {
        $file = $options['file'];
        $backupFilesystem = $this->storage->open($file);

        $database = $this->databaseFactory->createReadonly(
            json_decode($backupFilesystem->read('database/system.json'), true)
        );
        $this->output->writeln(sprintf(' * label:    %s', $database->get('label')));
        $this->output->writeln(sprintf(' * message:  %s', $database->get('message')));
        $this->output->writeln(sprintf(' * started:  %s', $database->get('started')));
        $this->output->writeln(sprintf(' * finished: %s', $database->get('finished')));
        $this->output->writeln(sprintf(' * size:     %s', (new ByteFormatter())->format($this->storage->size($file))));
        $this->output->writeln(sprintf(' * path:     %s', $this->storage->path($file)));
    }
}
