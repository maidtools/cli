<?php

namespace App\Commands;

use Exception;
use Maid\Sdk\Support\Manifest;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\ArrayShape;
use LaravelZero\Framework\Commands\Command;

class EnvCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'env {environment=production}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Create a new environment';

    public function handle(): int
    {
        $environment = Str::slug($this->argument('environment'));

        try {
            $manifest = Manifest::get();
        } catch (Exception $exception) {
            $this->warn($exception->getMessage());
        }

        $manifest['environments'][$environment] = $this->defaultConfiguration();

        Manifest::save($manifest);

        return self::SUCCESS;
    }

    #[ArrayShape(['build' => "string[]"])]
    private function defaultConfiguration(): array
    {
        return [
            'build' => [
                'composer install --no-dev',
            ],
        ];
    }
}
