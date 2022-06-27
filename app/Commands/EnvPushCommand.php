<?php

namespace App\Commands;

use App\Traits\InteractsWithMaidApi;
use Dotenv\Dotenv;
use Maid\Sdk\Exceptions\RequestRequiresClientIdException;
use Maid\Sdk\Maid;
use Maid\Sdk\Support\Manifest;
use GuzzleHttp\Exception\GuzzleException;
use LaravelZero\Framework\Commands\Command;

class EnvPushCommand extends Command
{
    use InteractsWithMaidApi;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'env:push {environment=production}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Upload the environment file for the given environment';

    /**
     * @throws RequestRequiresClientIdException
     * @throws GuzzleException
     */
    public function handle(Maid $maid): int
    {
        $manifest = Manifest::get();

        $filename = getcwd() . DIRECTORY_SEPARATOR . sprintf('.env.%s', $this->argument('environment'));

        $variables = Dotenv::parse(file_get_contents($filename));

        $result = $maid
            ->withUserAccessToken()
            ->setEnvironmentVariables($manifest['project'], array_map(function (string $value, string $key) {
                return [
                    'environment' => $this->argument('environment'),
                    'key' => strtoupper($key),
                    'value' => $value,
                ];
            }, $variables, array_keys($variables)));

        if ($result->success()) {
            $this->info('Environment has been uploaded.');

            return self::SUCCESS;
        }

        return $this->failure($result, 'Environment cannot be uploaded.');
    }
}
