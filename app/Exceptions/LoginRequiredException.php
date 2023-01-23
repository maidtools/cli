<?php

namespace App\Exceptions;

use DomainException;

class LoginRequiredException extends Exception
{
    public static function fromExpiredToken(): static
    {
        return new static('Your access token has expired. Please login again.');
    }

    public static function fromMissingCredentialsFile(): static
    {
        return new static('You are not logged in. Please login first.');
    }
}
