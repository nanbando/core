<?php

namespace Nanbando\Core\Storage;

class RemoteStorageNotConfiguredException extends \Exception
{
    public function __construct()
    {
        parent::__construct('RemoteStorage was not configured.');
    }
}
