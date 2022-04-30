<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;

class DeployCommand extends Command
{
    protected $signature = 'deploy {environment=staging}
                {--asset-url= : The asset base URL}
                {--build-arg=* : Docker build argument}';

    protected $description = 'Deploy the application';

    public function handle(): int
    {
        $this->info('Building the application...');

        $result = $this->call('build', [
            'environment' => $this->argument('environment'),
            '--asset-url' => $this->option('asset-url'),
            '--build-arg' => $this->option('build-arg'),
            '--revision' => $revision = 'latest',
        ]);

        if ($result !== self::SUCCESS) {
            $this->warn('Build failed!');

            return $result;
        }

        $this->info('Deploying the application...');

        $result = $this->call('redeploy', [
            'environment' => $this->argument('environment'),
            '--revision' => $revision,
            '--force' => true,
        ]);

        if ($result !== self::SUCCESS) {
            $this->warn('Deployment failed!');

            return $result;
        }

        return self::SUCCESS;
    }
}
