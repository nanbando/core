<?php

namespace Nanbando\Core\Server\Command\Local;

use Composer\Composer;
use Composer\IO\ConsoleIO;
use Dflydev\EmbeddedComposer\Core\EmbeddedComposerInterface;
use Nanbando\Core\Server\Command\CommandInterface;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * Install/Update dependencies.
 */
class LocalComposerCommand implements CommandInterface
{
    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var EmbeddedComposerInterface
     */
    private $embeddedComposer;

    /**
     * @var bool
     */
    private $update;

    public function __construct(
        InputInterface $input,
        OutputInterface $output,
        EmbeddedComposerInterface $embeddedComposer,
        bool $update
    ) {
        $this->input = $input;
        $this->output = $output;
        $this->embeddedComposer = $embeddedComposer;
        $this->update = $update;
    }

    /**
     * {@inheritdoc}
     */
    public function interact(InputInterface $input, OutputInterface $output)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $options = [])
    {
        $io = new ConsoleIO($this->input, $this->output, new HelperSet());
        $composer = $this->embeddedComposer->createComposer($io);
        $package = $composer->getPackage();
        $package->setStabilityFlags(array_merge($package->getStabilityFlags(),[
            'nanbando/core' => '20',
            'dflydev/embedded-composer' => '20',
        ]));

        $installer = $this->embeddedComposer->createInstaller($io, $composer);
        $installer
            ->setDryRun($options['--dry-run'])
            ->setVerbose($options['--verbose'])
            ->setPreferSource($options['--prefer-source'])
            ->setDevMode($options['--dev'])
            ->setRunScripts(!$options['--no-scripts'])
            ->setUpdate($this->update);

        $installer->run();
    }
}
