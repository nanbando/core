<?php

namespace Nanbando\Core\Environment;

/**
 * Encapsules interaction between backup/restore and the environment.
 */
interface EnvironmentInterface
{
    /**
     * An exception occurs during backup. Would you like to continue?
     *
     * @param \Exception $exception
     *
     * @return bool
     */
    public function continueFailedBackup(\Exception $exception);

    /**
     * An exception occurs during restore. Would you like to continue?
     *
     * @param \Exception $exception
     *
     * @return bool
     */
    public function continueFailedRestore(\Exception $exception);

    /**
     * An exception occurs during backup. Would you like to continue restoring it?
     *
     * @return bool
     */
    public function restorePartiallyBackup();
}
