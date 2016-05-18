<?php

namespace Nanbando\Bundle\Command;

use Composer\IO\NullIO;
use Dflydev\EmbeddedComposer\Core\EmbeddedComposerInterface;
use Puli\Discovery\Api\Type\BindingType;
use Puli\Manager\Api\Discovery\BindingTypeDescriptor;
use Puli\Manager\Api\Puli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Webmozart\PathUtil\Path;

class RebuildCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('rebuild');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $puli = new Puli(getcwd());
        $puli->start();

        /** @var EmbeddedComposerInterface $embeddedComposer */
        $embeddedComposer = $this->getApplication()->getEmbeddedComposer();

        $packageManager = $puli->getPackageManager();
        $composer = $embeddedComposer->createComposer(new NullIO());
        $installationManager = $composer->getInstallationManager();
        $rootPackage = $composer->getPackage();

        $repository = $composer->getRepositoryManager()->getLocalRepository();
        $packages = array();
        foreach ($repository->getPackages() as $package) {
            $packages[$package->getName()] = $package;
        }

        foreach ($rootPackage->getRequires() as $require) {
            if (!array_key_exists($require->getTarget(), $packages)) {
                continue;
            }

            $packageManager->installPackage(
                Path::normalize($installationManager->getInstallPath($packages[$require->getTarget()])),
                $require->getTarget(),
                'nanbando'
            );
        }

        $filesystem = new Filesystem();
        $filesystem->remove(Path::join([getcwd(), '/.puli']));

        $discoveryManager = $puli->getDiscoveryManager();
        if (!$discoveryManager->hasRootTypeDescriptor('nanbando/bundle')) {
            $discoveryManager->addRootTypeDescriptor(new BindingTypeDescriptor(new BindingType('nanbando/bundle')), 0);
        }

        $discoveryManager->clearDiscovery();
        $discoveryManager->buildDiscovery();

        $filesystem = new Filesystem();
        $filesystem->remove(Path::join([getcwd(), '/.nanbando/app/cache']));
    }
}
