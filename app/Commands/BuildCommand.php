<?php

namespace App\Commands;

use App\Services\Build;
use Exception;
use LaravelZero\Framework\Commands\Command;

class BuildCommand extends Command
{
    protected $signature = 'build {environment=staging}
                {--asset-url= : The asset base URL}
                {--build-arg=* : Docker build argument}
                {--revision= : Docker image version}';

    protected $description = 'Build the project container';

    public function handle(): int
    {
        try {
            $build = new Build(getcwd(), $this);
            $build->build();
        } catch (Exception $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
