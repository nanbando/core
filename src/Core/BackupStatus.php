<?php

namespace Nanbando\Core;

/**
 * Container for state constant.
 */
final class BackupStatus
{
    /**
     * State indicates successful backup.
     */
    const STATE_SUCCESS = 1;

    /**
     * State indicates failed backup.
     */
    const STATE_FAILED = 2;

    /**
     * State indicates partially-finished backup.
     */
    const STATE_PARTIALLY = 3;

    private function __construct()
    {
    }
}
