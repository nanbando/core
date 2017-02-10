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
        if (0 === $source->has($parameter['directory'])) {
            $this->output->writeln(sprintf('  Directory "%s" not found.', $parameter['directory']));

            return;
        }

        // TODO make it smoother
        $files = $source->listFiles($parameter['directory'], true);

        if (0 === count($files)) {
            $this->output->writeln(sprintf('  No files found in directory "%s".', $parameter['directory']));

            return;
        }

        $progressBar = new ProgressBar($this->output, count($files));
        $progressBar->setOverwrite(true);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $progressBar->start();

        $metadata = [];
        foreach ($files as $file) {
            $path = Path::makeRelative($file['path'], $parameter['directory']);
            $stream = $source->readStream($file['path']);
            if (!$stream) {
                continue;
            }

            $metadata[$path] = array_merge(['hash' => $this->getHash($stream)], $file);

            $destination->writeStream($path, $stream);
            fclose($stream);

            $progressBar->advance();
        }

        $database->set('metadata', $metadata);

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

        $metadata = $database->getWithDefault('metadata', []);

        foreach ($files as $file) {
            $path = $file['path'];
            $fullPath = $parameter['directory'] . '/' . $path;
            $fileMetadata = array_key_exists($path, $metadata) ? $metadata[$path] : ['hash' => null];
            if ($destination->has($fullPath)) {
                if ($fileMetadata['hash'] !== null && $destination->hash($fullPath) === $fileMetadata['hash']) {
                    $progressBar->advance();

                    continue;
                }

                $destination->delete($fullPath);
            }

            $stream = $source->readStream($path);
            if (!$stream) {
                continue;
            }

            $destination->writeStream($fullPath, $stream);
            fclose($stream);

            $progressBar->advance();
        }

        $progressBar->finish();
    }

    /**
     * Returns hash for resource.
     *
     * @param resource $stream
     * @param string $algorithm
     *
     * @return string
     */
    private function getHash($stream, $algorithm = 'sha256')
    {
        $hash = hash_init($algorithm);
        hash_update_stream($hash, $stream);

        return hash_final($hash);
    }
}
