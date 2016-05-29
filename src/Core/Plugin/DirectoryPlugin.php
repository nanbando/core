<?php

namespace Nanbando\Core\Plugin;

use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem;
use Nanbando\Core\Database\Database;
use Nanbando\Core\Database\ReadonlyDatabase;
use Symfony\Component\Console\Exception\LogicException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Webmozart\PathUtil\Path;

class DirectoryPlugin implements PluginInterface
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @param OutputInterface $output
     */
    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptionsResolver(OptionsResolver $optionsResolver)
    {
        $optionsResolver->setRequired('directory');
    }

    /**
     * {@inheritdoc}
     *
     * @throws FileExistsException
     * @throws \InvalidArgumentException
     * @throws FileNotFoundException
     * @throws LogicException
     */
    public function backup(Filesystem $source, Filesystem $destination, Database $database, array $parameter)
    {
        // TODO make it smoother
        $files = $source->listFiles($parameter['directory'], true);

        $progressBar = new ProgressBar($this->output, count($files));
        $progressBar->setOverwrite(true);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $progressBar->start();

        foreach ($files as $file) {
            $destination->writeStream(
                Path::makeRelative($file['path'], $parameter['directory']),
                $source->readStream($file['path'])
            );
            $progressBar->advance();
        }

        $progressBar->finish();
    }

    /**
     * {@inheritdoc}
     *
     * @throws FileExistsException
     * @throws \InvalidArgumentException
     * @throws FileNotFoundException
     * @throws LogicException
     */
    public function restore(
        Filesystem $source,
        Filesystem $destination,
        ReadonlyDatabase $database,
        array $parameter
    ) {
        // TODO make it smoother
        $files = $source->listFiles('', true);

        $progressBar = new ProgressBar($this->output, count($files));
        $progressBar->setOverwrite(true);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $progressBar->start();

        foreach ($files as $file) {
            $destination->writeStream(
                $parameter['directory'] . '/' . $file['path'],
                $source->readStream($file['path'])
            );
            $progressBar->advance();
        }

        $progressBar->finish();
    }
}
