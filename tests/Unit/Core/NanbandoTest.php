<?php

namespace Nanbando\Tests\Unit\Core;

use Cocur\Slugify\SlugifyInterface;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Flysystem\ZipArchive\ZipArchiveAdapter;
use Nanbando\Core\Database\Database;
use Nanbando\Core\Database\ReadonlyDatabase;
use Nanbando\Core\Flysystem\PrefixAdapter;
use Nanbando\Core\Flysystem\ReadonlyAdapter;
use Nanbando\Core\Nanbando;
use Nanbando\Core\Plugin\PluginInterface;
use Nanbando\Core\Plugin\PluginRegistry;
use Nanbando\Core\Temporary\TemporaryFileManager;
use Prophecy\Argument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Webmozart\PathUtil\Path;

class NanbandoTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    private $name = 'nanbando';

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var PluginRegistry
     */
    private $pluginRegistry;

    /**
     * @var Filesystem
     */
    private $localFilesystem;

    /**
     * @var TemporaryFileManager
     */
    private $temporaryFileManager;

    /**
     * @var SlugifyInterface
     */
    private $slugify;

    public function setUp()
    {
        $this->output = $this->prophesize(OutputInterface::class);
        $this->pluginRegistry = $this->prophesize(PluginRegistry::class);
        $this->localFilesystem = $this->prophesize(Filesystem::class);
        $this->temporaryFileManager = $this->prophesize(TemporaryFileManager::class);
        $this->slugify = $this->prophesize(SlugifyInterface::class);
    }

    protected function getNanbando(array $backup)
    {
        return new Nanbando(
            $this->name,
            $backup,
            $this->output->reveal(),
            $this->pluginRegistry->reveal(),
            $this->localFilesystem->reveal(),
            $this->temporaryFileManager->reveal(),
            $this->slugify->reveal()
        );
    }

    public function testBackup()
    {
        $nanbando = $this->getNanbando(
            [
                'uploads' => [
                    'plugin' => 'directory',
                    'parameter' => [
                        'directory' => 'uploads',
                    ],
                ],
            ]
        );

        $tempFile = tempnam('/tmp', 'nanbando');
        $this->temporaryFileManager->getFilename()->willReturn($tempFile);

        $plugin = $this->prophesize(PluginInterface::class);
        $this->pluginRegistry->getPlugin('directory')->willReturn($plugin->reveal());
        $plugin->configureOptionsResolver(Argument::type(OptionsResolver::class))
            ->will(
                function ($args) {
                    $args[0]->setRequired(['directory']);
                }
            );
        $plugin->backup(
            Argument::that(
                function (Filesystem $filesystem) {
                    /** @var ReadonlyAdapter $adapter */
                    $adapter = $filesystem->getAdapter();

                    $this->assertInstanceOf(ReadonlyAdapter::class, $adapter);

                    return $adapter->getAdapter()->getPathPrefix() === realpath('.') . '/';
                }
            ),
            Argument::that(
                function (Filesystem $filesystem) {
                    /** @var PrefixAdapter $adapter */
                    $adapter = $filesystem->getAdapter();

                    return $adapter->getRoot() === 'backup/uploads';
                }
            ),
            Argument::type(Database::class),
            [
                'directory' => 'uploads',
            ]
        )->shouldBeCalled();

        $this->localFilesystem->putStream(Argument::any(), Argument::any())->shouldBeCalled();

        $nanbando->backup();

        $zipFile = new ZipArchiveAdapter($tempFile);
        $files = $zipFile->listContents();

        $fileNames = array_map(
            function ($item) {
                return $item['path'];
            },
            $files
        );

        $this->assertEquals(
            [
                'database/backup',
                'database/backup/uploads.json',
                'database',
                'database/system.json',
            ],
            $fileNames
        );
    }

    public function testRestore()
    {
        $path = Path::join([RESOURCE_DIR, 'backups', '13-21-45-2016-05-29.zip']);
        $nanbando = $this->getNanbando(
            [
                'uploads' => [
                    'plugin' => 'directory',
                    'parameter' => [
                        'directory' => 'uploads',
                    ],
                ],
            ]
        );

        $plugin = $this->prophesize(PluginInterface::class);
        $this->pluginRegistry->getPlugin('directory')->willReturn($plugin->reveal());
        $plugin->configureOptionsResolver(Argument::type(OptionsResolver::class))
            ->will(
                function ($args) {
                    $args[0]->setRequired(['directory']);
                }
            );
        $plugin->restore(
            Argument::that(
                function (Filesystem $filesystem) {
                    /** @var ReadonlyAdapter $adapter */
                    $adapter = $filesystem->getAdapter();

                    $this->assertInstanceOf(ReadonlyAdapter::class, $adapter);

                    return $adapter->getAdapter()->getRoot() === 'backup/uploads';
                }
            ),
            Argument::that(
                function (Filesystem $filesystem) {
                    /** @var Local $adapter */
                    $adapter = $filesystem->getAdapter();

                    return $adapter->getPathPrefix() === realpath('.') . '/';
                }
            ),
            Argument::type(ReadonlyDatabase::class),
            [
                'directory' => 'uploads',
            ]
        )->shouldBeCalled();

        $nanbando->restore($path);
    }
}
