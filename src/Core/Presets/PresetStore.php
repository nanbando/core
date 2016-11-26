<?php

namespace Nanbando\Core\Presets;

use Composer\Semver\Semver;

/**
 * Contains presets and provide functionality to get satisfied presets.
 */
class PresetStore
{
    /**
     * @var array
     */
    private $presets = [];

    /**
     * @param array $presets
     */
    public function __construct(array $presets)
    {
        $this->presets = $presets;
    }

    /**
     * Returns preset for given application and version.
     *
     * @param $application
     * @param $version
     * @param array $options
     *
     * @return array
     */
    public function getPreset($application, $version, array $options = null)
    {
        $presets = [];
        foreach ($this->presets as $preset) {
            if ($preset['application'] === $application
                && $this->matchVersion($version, $preset)
                && $this->matchOptions($options, $preset)
            ) {
                $presets[] = $preset['backup'];
            }
        }

        if (0 === count($presets)) {
            return [];
        }
        if (1 === count($presets)) {
            return $presets[0];
        }

        return call_user_func_array('array_merge', $presets);
    }

    /**
     * Matches actual version with preset version.
     *
     * @param string $actual
     * @param array $preset
     *
     * @return bool
     */
    private function matchVersion($actual, array $preset)
    {
        if (!$actual || !array_key_exists('version', $preset)) {
            return false;
        }

        return Semver::satisfies($actual, $preset['version']);
    }

    /**
     * Matches actual options with preset options.
     *
     * @param array $actual
     * @param array $preset
     *
     * @return bool
     */
    private function matchOptions($actual, array $preset)
    {
        if (!$actual || !array_key_exists('options', $preset)) {
            return true;
        }

        foreach ($actual as $key => $value) {
            if (array_key_exists($key, $preset['options']) && $value !== $preset['options'][$key]) {
                return false;
            }
        }

        return true;
    }
}
