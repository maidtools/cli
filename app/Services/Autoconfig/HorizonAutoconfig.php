<?php

namespace App\Services\Autoconfig;

use LaravelZero\Framework\Commands\Command;

class HorizonAutoconfig extends Resolver
{
    public function __construct()
    {
        parent::__construct('laravel/horizon');
    }

    public function getEnvironmentConfig(Command $command): array
    {
        return [
            'horizon' => true,
        ];
    }
}
