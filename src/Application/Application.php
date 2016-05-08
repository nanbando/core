<?php

namespace Nanbando\Application;

use Dflydev\EmbeddedComposer\Core\EmbeddedComposerAwareInterface;
use Dflydev\EmbeddedComposer\Core\EmbeddedComposerInterface;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\HttpKernel\KernelInterface;

class Application extends SymfonyApplication implements EmbeddedComposerAwareInterface
{
    const GIT_VERSION = '@git_version@';

    /**
     * @var EmbeddedComposerInterface
     */
    private $embeddedComposer;

    /**
     * @param KernelInterface $kernel
     * @param EmbeddedComposerInterface $embeddedComposer
     */
    public function __construct(KernelInterface $kernel, EmbeddedComposerInterface $embeddedComposer)
    {
        $this->embeddedComposer = $embeddedComposer;

        $version = $embeddedComposer->findPackage('nanbando/core')->getPrettyVersion();
        if ($version !== self::GIT_VERSION && self::GIT_VERSION !== '@' . 'git_version' . '@') {
            $version .= ' (' . self::GIT_VERSION . ')';
        }

        parent::__construct(
            'Nanbando',
            sprintf(
                '%s - %s / %s%s',
                $version,
                $kernel->getName(),
                $kernel->getEnvironment(),
                ($kernel->isDebug() ? '/debug' : '')
            )
        );

        foreach ($kernel->getBundles() as $bundle) {
            $bundle->registerCommands($this);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getEmbeddedComposer()
    {
        return $this->embeddedComposer;
    }
}
