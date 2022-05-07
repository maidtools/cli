<?php

namespace App\Services\Autoconfig;

use LaravelZero\Framework\Commands\Command;

abstract class Resolver
{
    private string $package;

    public function __construct(string $package)
    {
        $this->package = $package;
    }

    abstract public function getEnvironmentConfig(Command $command): array;

    public function getPackage(): string
    {
        return $this->package;
    }
}
