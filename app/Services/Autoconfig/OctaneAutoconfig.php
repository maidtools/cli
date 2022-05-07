<?php

namespace App\Services\Autoconfig;

use LaravelZero\Framework\Commands\Command;

class OctaneAutoconfig extends Resolver
{
    public function __construct()
    {
        parent::__construct('laravel/octane');
    }

    public function getEnvironmentConfig(Command $command): array
    {
        return [
            'octane' => true,
        ];
    }
}
