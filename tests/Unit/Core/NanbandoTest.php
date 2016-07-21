<?php

namespace Nanbando\Tests\Unit\Core;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Flysystem\Memory\MemoryAdapter;
use League\Flysystem\ZipArchive\ZipArchiveAdapter;
use Nanbando\Core\Database\Database;
use Nanbando\Core\Database\DatabaseFactory;
use Nanbando\Core\Database\ReadonlyDatabase;
use Nanbando\Core\Environment\EnvironmentInterface;
use Nanbando\Core\Flysystem\PrefixAdapter;
use Nanbando\Core\Flysystem\ReadonlyAdapter;
use Nanbando\Core\Nanbando;
use Nanbando\Core\Plugin\PluginInterface;
use Nanbando\Core\Plugin\PluginRegistry;
use Nanbando\Core\Storage\StorageInterface;
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
     * @var StorageInterface
     */
    private $storage;

    /**
     * @var DatabaseFactory
     */
    private $databaseFactory;

    /**
     * @var EnvironmentInterface
     */
    private $environment;

    public function setUp()
    {
        $this->output = $this->prophesize(OutputInterface::class);
        $this->pluginRegistry = $this->prophesize(PluginRegistry::class);
        $this->storage = $this->prophesize(StorageInterface::class);
        $this->databaseFactory = $this->prophesize(DatabaseFactory::class);
        $this->environment = $this->prophesize(EnvironmentInterface::class);

        $this->databaseFactory->create(Argument::any())->will(function ($data) {
            return new Database(isset($data[0]) ? $data[0] : []);
        });
        $this->databaseFactory->createReadonly(Argument::any())->will(function ($data) {
            return new ReadonlyDatabase(isset($data[0]) ? $data[0] : []);
        });
    }

    protected function getNanbando(array $backup)
    {
        return new Nanbando(
            $this->name,
            $backup,
            $this->output->reveal(),
            $this->pluginRegistry->reveal(),
            $this->storage->reveal(),
            $this->databaseFactory->reveal(),
            $this->environment->reveal()
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

        $this->environment->continueFailedBackup(Argument::any())->willReturn(true);
        $this->environment->continueFailedRestore(Argument::any())->willReturn(true);
        $this->environment->restorePartiallyBackup()->willReturn(true);

        $tempFile = tempnam('/tmp', 'nanbando');
        $filesystem = new Filesystem(new ZipArchiveAdapter($tempFile));
        $this->storage->start()->willReturn($filesystem);

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

        $this->storage->close($filesystem)->shouldBeCalled();

        $this->assertEquals(Nanbando::STATE_SUCCESS, $nanbando->backup());

        $filesystem->getAdapter()->getArchive()->close();

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
        $path = Path::join([DATAFIXTURES_DIR, 'backups', '13-21-2016-05-29_success.zip']);
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

        $this->environment->continueFailedBackup(Argument::any())->willReturn(true);
        $this->environment->continueFailedRestore(Argument::any())->willReturn(true);
        $this->environment->restorePartiallyBackup()->willReturn(true);

        $filesystem = new Filesystem(new ZipArchiveAdapter($path));
        $this->storage->open('13-21-45-2016-05-29')->willReturn($filesystem);

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

                    $this->assertInstanceOf(PrefixAdapter::class, $adapter);

                    return $adapter->getRoot() === 'backup/uploads';
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

        $nanbando->restore('13-21-45-2016-05-29');
    }

    public function testRestorePartiallyBackup()
    {
        $path = Path::join([DATAFIXTURES_DIR, 'backups', '31-07-2016-10-39_failed.zip']);
        $nanbando = $this->getNanbando(
            [
                'uploads' => [
                    'plugin' => 'directory',
                    'parameter' => [
                        'directory' => 'uploads',
                    ],
                ],
                'src' => [
                    'plugin' => 'database',
                    'parameter' => [
                        'directory' => 'src',
                    ],
                ],
            ]
        );

        $this->environment->continueFailedBackup(Argument::any())->willReturn(true);
        $this->environment->continueFailedRestore(Argument::any())->willReturn(true);
        $this->environment->restorePartiallyBackup()->willReturn(true);

        $filesystem = new Filesystem(new ZipArchiveAdapter($path));
        $this->storage->open('31-07-2016-10-39_failed')->willReturn($filesystem);

        $directoryPlugin = $this->prophesize(PluginInterface::class);
        $this->pluginRegistry->getPlugin('directory')->willReturn($directoryPlugin->reveal());
        $directoryPlugin->configureOptionsResolver(Argument::type(OptionsResolver::class))
            ->will(
                function ($args) {
                    $args[0]->setRequired(['directory']);
                }
            );
        $directoryPlugin->restore(
            Argument::that(
                function (Filesystem $filesystem) {
                    /** @var ReadonlyAdapter $adapter */
                    $adapter = $filesystem->getAdapter();

                    $this->assertInstanceOf(PrefixAdapter::class, $adapter);

                    return $adapter->getRoot() === 'backup/uploads';
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

        $databasPlugin = $this->prophesize(PluginInterface::class);
        $databasPlugin->backup()->shouldNotBeCalled();
        $databasPlugin->configureOptionsResolver(Argument::type(OptionsResolver::class))
            ->will(
                function ($args) {
                    $args[0]->setRequired(['directory']);
                }
            );
        $this->pluginRegistry->getPlugin('database')->willReturn($databasPlugin->reveal());

        $nanbando->restore('31-07-2016-10-39_failed');
    }

    public function testExceptionPlugin()
    {
        $nanbando = $this->getNanbando(
            [
                'uploads' => [
                    'plugin' => 'directory',
                    'parameter' => [],
                ],
            ]
        );

        $this->environment->continueFailedBackup(Argument::any())->willReturn(true);
        $this->environment->continueFailedRestore(Argument::any())->willReturn(true);
        $this->environment->restorePartiallyBackup()->willReturn(true);

        $filesystem = new Filesystem(new MemoryAdapter());
        $this->storage->start()->willReturn($filesystem);

        $plugin = $this->prophesize(PluginInterface::class);
        $this->pluginRegistry->getPlugin('directory')->willReturn($plugin->reveal());
        $plugin->configureOptionsResolver(Argument::type(OptionsResolver::class))->shouldBeCalled();
        $plugin->backup(Argument::cetera())->willThrow(new \Exception('Test-Exception', 200))->shouldBeCalled();
        $this->storage->close($filesystem)->shouldBeCalled();

        $this->assertEquals(Nanbando::STATE_PARTIALLY, $nanbando->backup());

        $database = new Database(json_decode($filesystem->read('database/backup/uploads.json'), true));
        $exception = $database->get('exception');

        $this->assertEquals(Nanbando::STATE_FAILED, $database->get('state'));

        $this->assertNull($exception['previous']);
        $this->assertNotNull($exception['trace']);
        $this->assertNotNull($exception['file']);
        $this->assertNotNull($exception['line']);

        $this->assertEquals('Test-Exception', $exception['message']);
        $this->assertEquals(200, $exception['code']);

        $database = new Database(json_decode($filesystem->read('database/system.json'), true));
        $this->assertEquals(Nanbando::STATE_PARTIALLY, $database->get('state'));
    }

    public function testExceptionPluginNotContinue()
    {
        $nanbando = $this->getNanbando(
            [
                'uploads' => [
                    'plugin' => 'directory',
                    'parameter' => [],
                ],
                'src' => [
                    'plugin' => 'directory',
                    'parameter' => [],
                ],
            ]
        );

        $this->environment->continueFailedBackup(Argument::any())->willReturn(false);
        $this->environment->continueFailedRestore(Argument::any())->willReturn(true);
        $this->environment->restorePartiallyBackup()->willReturn(true);

        $filesystem = new Filesystem(new MemoryAdapter());
        $this->storage->start()->willReturn($filesystem);

        $plugin = $this->prophesize(PluginInterface::class);
        $this->pluginRegistry->getPlugin('directory')->willReturn($plugin->reveal());
        $plugin->configureOptionsResolver(Argument::type(OptionsResolver::class))->shouldBeCalled();
        $plugin->backup(Argument::cetera())->willThrow(new \Exception('Test-Exception', 200))->shouldBeCalledTimes(1);

        $this->assertEquals(Nanbando::STATE_FAILED, $nanbando->backup());
    }
}
