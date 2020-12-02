<?php

namespace Nanbando\Application;

use Dflydev\EmbeddedComposer\Core\EmbeddedComposerAwareInterface;
use Dflydev\EmbeddedComposer\Core\EmbeddedComposerInterface;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class Application extends SymfonyApplication implements EmbeddedComposerAwareInterface
{
    const GIT_VERSION = '@git_version@';

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var EmbeddedComposerInterface
     */
    private $embeddedComposer;

    public function __construct(KernelInterface $kernel, EmbeddedComposerInterface $embeddedComposer)
    {
        $this->kernel = $kernel;
        $this->embeddedComposer = $embeddedComposer;

        $version = $embeddedComposer->findPackage('nanbando/core')->getPrettyVersion();
        if ($version !== self::GIT_VERSION && self::GIT_VERSION !== '@' . 'git_version' . '@') {
            $version .= ' (' . self::GIT_VERSION . ')';
        }

        parent::__construct('Nanbando', sprintf('%s - nanbando', $version));

        foreach ($kernel->getBundles() as $bundle) {
            $bundle->registerCommands($this);
        }

        /** @var CommandLoaderInterface $commandLoader */
        $commandLoader = $kernel->getContainer()->get('console.command_loader');
        foreach ($commandLoader->getNames() as $name) {
            $this->add($commandLoader->get($name));
        }
    }

    /**
     * @return KernelInterface
     */
    public function getKernel()
    {
        return $this->kernel;
    }

    /**
     * {@inheritdoc}
     */
    public function getEmbeddedComposer()
    {
        return $this->embeddedComposer;
    }
}
