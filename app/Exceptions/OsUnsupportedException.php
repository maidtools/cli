<?php

namespace App\Exceptions;

class OsUnsupportedException extends Exception
{
    public static function supported(array $supported): self
    {
        return new self(sprintf(
            "running on unsupported OS: %s - only %s supported.",
            PHP_OS_FAMILY,
            implode(', ', $supported)
        ));
    }
}
