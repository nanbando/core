<?php

namespace Nanbando\Core\Environment;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Uses console to determine answer on questions.
 */
class ConsoleEnvironment implements EnvironmentInterface
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var QuestionHelper
     */
    private $questionHelper;

    /**
     * @param OutputInterface $output
     * @param InputInterface $input
     */
    public function __construct(OutputInterface $output, InputInterface $input)
    {
        $this->output = $output;
        $this->input = $input;
        $this->questionHelper = new QuestionHelper();
    }

    /**
     * {@inheritdoc}
     */
    public function continueFailedBackup(\Exception $exception)
    {
        $this->output->writeln(sprintf('  <error>Failed: %s</error>', $exception->getMessage()));

        return $this->questionHelper->ask(
            $this->input,
            $this->output,
            new ConfirmationQuestion('Would you like to continue?')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function continueFailedRestore(\Exception $exception)
    {
        $this->output->writeln(sprintf('  <error>Failed: %s</error>', $exception->getMessage()));

        return $this->questionHelper->ask(
            $this->input,
            $this->output,
            new ConfirmationQuestion('Would you like to continue?')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function restorePartiallyBackup()
    {
        $this->output->writeln('');
        $this->output->writeln('Selected backup was finished partially.');

        return $this->questionHelper->ask(
            $this->input,
            $this->output,
            new ConfirmationQuestion('Would you like to continue?')
        );
    }
}
