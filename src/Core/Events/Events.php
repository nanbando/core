<?php

namespace Nanbando\Core\Events;

/**
 * Container for event names.
 */
final class Events
{
    const PRE_BACKUP_EVENT = 'nanbando.pre_backup';
    const BACKUP_EVENT = 'nanbando.backup';
    const POST_BACKUP_EVENT = 'nanbando.post_backup';

    const PRE_RESTORE_EVENT = 'nanbando.pre_restore';
    const RESTORE_EVENT = 'nanbando.restore';
    const POST_RESTORE_EVENT = 'nanbando.post_restore';

    private function __construct()
    {
    }
}
