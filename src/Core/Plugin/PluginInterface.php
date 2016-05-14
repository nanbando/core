<?php

namespace Nanbando\Core\Plugin;

use League\Flysystem\Filesystem;
use Nanbando\Core\Database\Database;
use Nanbando\Core\Database\ReadonlyDatabase;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\OptionsResolver;

interface PluginInterface
{
    /**
     * @param OptionsResolver $optionsResolver
     *
     * @throws AccessException
     */
    public function configureOptionsResolver(OptionsResolver $optionsResolver);

    /**
     * @param Filesystem $source
     * @param Filesystem $destination
     * @param Database $database
     * @param array $parameter
     */
    public function backup(Filesystem $source, Filesystem $destination, Database $database, array $parameter);

    /**
     * @param Filesystem $source
     * @param Filesystem $destination
     * @param ReadonlyDatabase $database
     * @param array $parameter
     */
    public function restore(
        Filesystem $source,
        Filesystem $destination,
        ReadonlyDatabase $database,
        array $parameter
    );
}
