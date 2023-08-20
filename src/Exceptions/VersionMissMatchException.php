<?php

namespace App\Exceptions;

use Exception;

class VersionMissMatchException extends Exception
{
    public function __construct(string|int $firstVersion, string|int $secondVersion)
    {
        parent::__construct(sprintf(
            'Expected %s to match %s',
            $firstVersion,
            $secondVersion
        ));
    }
}
