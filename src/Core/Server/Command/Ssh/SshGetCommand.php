<?php

namespace Nanbando\Core\Server\Command\Ssh;

use Nanbando\Core\Server\Command\CommandInterface;
use Nanbando\Core\Storage\StorageInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * Get a backup from a ssh connected server.
 */
class SshGetCommand implements CommandInterface
{
    /**
     * @var SshConnection
     */
    private $connection;

    /**
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @param SshConnection $connection
     * @param StorageInterface $storage
     * @param OutputInterface $output
     */
    public function __construct(SshConnection $connection, StorageInterface $storage, OutputInterface $output)
    {
        $this->connection = $connection;
        $this->storage = $storage;
        $this->output = $output;
    }

    /**
     * {@inheritdoc}
     */
    public function interact(InputInterface $input, OutputInterface $output)
    {
        if ($input->getArgument('name')) {
            return;
        }

        $files = [];
        $this->connection->executeNanbando(
            'list:backups',
            [],
            function ($line) use (&$files) {
                if (empty($line) || $line === "\n") {
                    return;
                }

                $files[] = $line;
            }
        );

        if ($input->getOption('latest') && count($files) > 0) {
            return $input->setArgument('name', end($files));
        } elseif (count($files) === 1) {
            return $input->setArgument('name', $files[0]);
        }

        $helper = new QuestionHelper();
        $question = new ChoiceQuestion('Which backup', $files);
        $question->setErrorMessage('Backup %s is invalid.');
        $question->setAutocompleterValues([]);

        $input->setArgument('name', $helper->ask($input, $output, $question));
        $output->writeln('');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $options = [])
    {
        $name = $options['name'];

        $information = $this->connection->executeNanbando('information', [$name]);
        $this->output->writeln($information);

        preg_match('/path:\s*(?<path>\/([^\/\0]+(\/)?)+)\n/', $information, $matches);
        $remotePath = $matches['path'];

        $localPath = $this->storage->path($name);
        $this->output->writeln(PHP_EOL . '$ scp ' . $remotePath . ' ' . $localPath);

        // Try to display progress somehow.
        $this->connection->get($remotePath, $localPath);

        $this->output->writeln(PHP_EOL . sprintf('Backup "%s" downloaded successfully', $name));

        return $name;
    }
}
