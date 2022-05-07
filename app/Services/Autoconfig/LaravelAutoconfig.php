<?php

namespace App\Services\Autoconfig;

use App\Exceptions\Exception;
use Dotenv\Dotenv;
use LaravelZero\Framework\Commands\Command;

class LaravelAutoconfig extends Resolver
{
    public function __construct()
    {
        parent::__construct('laravel/framework');
    }

    public function getEnvironmentConfig(Command $command): array
    {
        $environment = [];

        $image = $command->ask('Which container image do you want to use as a base?', 'maid.sh/laravel:8.1-octane-minimal');
        $environment['image'] = $image;

        try {
            $filename = $this->getEnvironmentFilename($command);
        } catch (Exception $exception) {
            $command->warn($exception->getMessage());
        }

        if (isset($filename) && file_exists($filename)) {
            $variables = Dotenv::parse(file_get_contents($filename));

            if (!empty($variables['DB_CONNECTION']) && $variables['DB_CONNECTION'] === 'mysql') {
                $database = $command->ask('How would you like to name the database?', 'app-db');
                $environment['database'] = $database;
            }

            if (!empty($variables['REDIS_HOST'])) {
                $cache = $command->ask('How would you like to name the cache?', 'app-cache');
                $environment['cache'] = $cache;
            }

            if (!empty($variables['AWS_ACCESS_KEY_ID'])) {
                $storage = $command->ask('How would you like to name the storage?', 'app-storage');
                $environment['storage'] = $storage;
            }
        }

        return array_merge($environment, [
            'build' => ['composer install --no-dev']
        ]);
    }

    private function getEnvironmentFilename(Command $command): string
    {
        if (file_exists($filename = sprintf('%s%s.env', getcwd(), DIRECTORY_SEPARATOR))) {
            return $filename;
        }

        if (file_exists($filename = sprintf('%s%s.env.example', getcwd(), DIRECTORY_SEPARATOR))) {
            if ($command->confirm('There is no .env in the project do you want to use the .env.example?', true)) {
                return $filename;
            }
        }

        throw new Exception('Unfortunately I could not find your environment file.');
    }
}
