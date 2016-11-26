<?php

namespace Unit\Core\Presets;

use Nanbando\Core\Presets\PresetStore;

/**
 * Tests for preset-store.
 */
class PresetStoreTest extends \PHPUnit_Framework_TestCase
{
    public function presetProvider()
    {
        return [
            [
                [
                    'sulu-1.3' => ['application' => 'sulu', 'version' => '^1.3', 'backup' => ['data' => []]],
                ],
                'sulu',
                '1.3.1',
                null,
                ['data' => []],
            ],
            [
                [
                    'sulu-1.3' => ['application' => 'sulu', 'version' => '^1.3', 'backup' => ['data' => []]],
                    'sulu-0.0' => ['application' => 'sulu', 'version' => '<1.0', 'backup' => ['data' => ['x']]],
                    'sulu-1.2' => ['application' => 'sulu', 'version' => '>1.0 <1.3', 'backup' => ['data' => ['x']]],
                ],
                'sulu',
                '1.3.1',
                null,
                ['data' => []],
            ],
            [
                [
                    'sulu-1.3' => ['application' => 'sulu', 'version' => '^1.3', 'backup' => ['data' => ['x']]],
                    'sulu-0.0' => ['application' => 'sulu', 'version' => '<1.0', 'backup' => ['data' => ['x']]],
                    'sulu-1.2' => ['application' => 'sulu', 'version' => '>1.0 <1.3', 'backup' => ['data' => []]],
                ],
                'sulu',
                '1.2.0',
                null,
                ['data' => []],
            ],
            [
                [
                    'sulu-1.3' => [
                        'application' => 'sulu',
                        'version' => '^1.3',
                        'options' => ['database' => 'jackrabbit'],
                        'backup' => ['data' => ['x']],
                    ],
                    'sulu-0.0' => ['application' => 'sulu', 'version' => '<1.0', 'backup' => ['data' => ['x']]],
                    'sulu-1.2' => ['application' => 'sulu', 'version' => '>1.0 <1.3', 'backup' => ['data' => []]],
                ],
                'sulu',
                '1.2.0',
                null,
                ['data' => []],
            ],
            [
                [
                    [
                        'application' => 'sulu',
                        'version' => '^1.3',
                        'options' => ['database' => 'mysql'],
                        'backup' => ['data' => ['x']],
                    ],
                    [
                        'application' => 'sulu',
                        'version' => '^1.3',
                        'options' => ['database' => 'jackrabbit'],
                        'backup' => ['data' => []],
                    ],
                ],
                'sulu',
                '1.3.0',
                ['database' => 'jackrabbit'],
                ['data' => []],
            ],
            [
                [
                    [
                        'application' => 'sulu',
                        'version' => '^1.3',
                        'options' => ['database' => 'mysql', 'edition' => 'standard'],
                        'backup' => ['data' => ['x']],
                    ],
                    [
                        'application' => 'sulu',
                        'version' => '^1.3',
                        'options' => ['database' => 'jackrabbit', 'edition' => 'minimal'],
                        'backup' => ['data' => []],
                    ],
                ],
                'sulu',
                '1.3.0',
                ['database' => 'jackrabbit'],
                ['data' => []],
            ],
            [
                [
                    'sulu-1.3' => ['application' => 'sulu', 'version' => '^1.3', 'backup' => ['data' => ['x']]],
                    'sulu-0.0' => ['application' => 'sulu', 'version' => '<1.0', 'backup' => ['data' => ['x']]],
                    'sulu-1.2' => ['application' => 'sulu', 'version' => '>1.0 <1.3', 'backup' => ['data' => []]],
                ],
                'sulu',
                '2.0.0',
                null,
                [],
            ],
            [
                [
                    'sulu-1.3' => ['application' => 'sulu', 'version' => '^1.3', 'backup' => ['data' => ['x']]],
                    'sulu-0.0' => ['application' => 'sulu', 'version' => '<1.0', 'backup' => ['data' => ['x']]],
                    'sulu-1.2' => ['application' => 'sulu', 'version' => '>1.0 <1.3', 'backup' => ['data' => []]],
                ],
                'magento',
                '1.3.0',
                null,
                [],
            ],
            [
                [
                    'sulu-1.3' => [
                        'application' => 'sulu',
                        'version' => '^1.3',
                        'options' => ['database' => 'jackrabbit'],
                        'backup' => ['data' => ['x']],
                    ],
                    'sulu-0.0' => ['application' => 'sulu', 'version' => '<1.0', 'backup' => ['data' => ['x']]],
                    'sulu-1.2' => ['application' => 'sulu', 'version' => '>1.0 <1.3', 'backup' => ['data' => ['x']]],
                ],
                'sulu',
                '1.3.0',
                ['database' => 'mysql'],
                [],
            ],
            [
                [
                    'sulu-1.3' => [
                        'application' => 'sulu',
                        'version' => '^1.3',
                        'options' => ['database' => 'jackrabbit'],
                        'backup' => ['data' => ['x']],
                    ],
                    'sulu-0.0' => [
                        'application' => 'sulu',
                        'version' => '<1.0',
                        'options' => ['database' => 'mysql', 'edition' => 'standard'],
                        'backup' => ['data' => ['x']],
                    ],
                    'sulu-1.2' => [
                        'application' => 'sulu',
                        'options' => ['database' => 'mysql', 'edition' => 'standard'],
                        'version' => '>1.0 <1.3',
                        'backup' => ['data' => ['x']],
                    ],
                ],
                'sulu',
                '1.3.0',
                ['database' => 'mysql'],
                [],
            ],
        ];
    }

    /**
     * @dataProvider presetProvider
     *
     * @param array $presets
     * @param string $application
     * @param string $version
     * @param array|null $options
     * @param array $expects
     */
    public function testGetPreset($presets, $application, $version, $options, $expects)
    {
        $presetStore = new PresetStore($presets);

        $this->assertEquals($expects, $presetStore->getPreset($application, $version, $options));
    }
}
