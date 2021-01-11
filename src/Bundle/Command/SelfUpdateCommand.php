<?php

namespace Nanbando\Bundle\Command;

use Humbug\SelfUpdate\Strategy\GithubStrategy;
use Humbug\SelfUpdate\Updater;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SelfUpdateCommand extends Command
{
    protected static $defaultName = 'self-update';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::$defaultName)
            ->setDescription('Updates the application.')
            ->addOption('nightly', null, InputOption::VALUE_NONE, 'Force an update to nightly channel');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $updater = new Updater();

        if ($input->getOption('nightly')) {
            $this->nightly($updater);
        } else {
            $this->stable($updater);
        }

        $result = $updater->update();
        if (!$result) {
            $output->writeln('You are already using "' . $updater->getNewVersion() . '" version.');

            // No update needed!
            return 1;
        }

        $upgradeFile = 'phar://' . $updater->getLocalPharFile() . '/UPGRADE.md';
        if (file_exists($upgradeFile)) {
            $output->writeln(@file_get_contents($upgradeFile));
            $output->writeln('');
        }

        $output->writeln(sprintf('Updated from %s to %s', $updater->getOldVersion(), $updater->getNewVersion()));

        return 1;
    }

    /**
     * Configure updater to use nightly builds.
     *
     * @param Updater $updater
     */
    private function nightly(Updater $updater)
    {
        $updater->getStrategy()->setPharUrl('http://nanbando.github.io/core/nanbando.phar');
        $updater->getStrategy()->setVersionUrl('http://nanbando.github.io/core/nanbando.phar.version');
    }

    /**
     * Configure updater to use unstable builds.
     *
     * @param Updater $updater
     */
    private function stable(Updater $updater)
    {
        $updater->setStrategy(Updater::STRATEGY_GITHUB);
        $updater->getStrategy()->setPackageName('nanbando/core');
        $updater->getStrategy()->setPharName('nanbando.phar');
        $updater->getStrategy()->setCurrentLocalVersion('@git_version@');
        $updater->getStrategy()->setStability(GithubStrategy::STABLE);
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        return false !== strpos(__DIR__, 'phar:');
    }
}
