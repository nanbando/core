Presets
=======

Presets are an easy way to integrate your application (e.g. `Sulu Plugin`_) into the nanbando system.
Presets are backup-configurations for specific applications and versions.

Inside a bundle the extension is able to prepend presets for different applications, versions and options.

.. code:: php

    <?php

    namespace Nanbando\Plugin\Sulu\DependencyInjection;

    use Symfony\Component\DependencyInjection\ContainerBuilder;
    use Symfony\Component\DependencyInjection\Extension\Extension;
    use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;

    /**
     * Integrates sulu presets into nanbando.
     */
    class NanbandoSuluExtension extends Extension implements PrependExtensionInterface
    {
        /**
         * {@inheritdoc}
         */
        public function prepend(ContainerBuilder $container)
        {

        $container->prependExtensionConfig(
            'nanbando',
            [
                'presets' => [
                    [
                        'application' => 'sulu',
                        'version' => '*',
                        'backup' => [
                            'database' => [
                                'plugin' => 'mysql',
                                'parameter' => [
                                    'username' => '%database_user%',
                                    'password' => '%database_password%',
                                    'database' => '%database_name%',
                                ],
                            ],
                        ],
                    ],
                ]
            );
        }

        /**
         * {@inheritdoc}
         */
        public function load(array $configs, ContainerBuilder $container)
        {
        }
    }

.. _`Sulu Plugin`: https://github.com/nanbando/sulu
