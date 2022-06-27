<?php

namespace App\Commands;

use Maid\Sdk\Exceptions\RequestRequiresClientIdException;
use Maid\Sdk\Maid;
use Maid\Sdk\Support\Manifest;
use GuzzleHttp\Exception\GuzzleException;
use LaravelZero\Framework\Commands\Command;

class EnvPullCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'env:pull {environment=production}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Download the environment file for the given environment';

    /**
     * @throws RequestRequiresClientIdException
     * @throws GuzzleException
     */
    public function handle(Maid $maid): int
    {
        $manifest = Manifest::get();

        $filename = getcwd() . DIRECTORY_SEPARATOR . sprintf('.env.%s', $this->argument('environment'));

        if (file_exists($filename)) {
            if (!$this->confirm(sprintf(
                'Are you sure to override the current %s environment?',
                $this->argument('environment')
            ))) {
                return self::FAILURE;
            }
        }

        $variables = ['# pulled from maid.sh (without default variables)'];
        $paginator = null;

        do {
            $result = $maid
                ->withUserAccessToken()
                ->getEnvironmentVariables($manifest['project'], $this->argument('environment'), $paginator);

            foreach ($result->data() as $environment) {
                $value = addslashes($environment->value);
                if (str_contains($value, ' ')) {
                    $variables[] = "{$environment->key}=\"{$value}\"";
                } else {
                    $variables[] = "{$environment->key}={$value}";
                }
            }
        } while ($paginator = $result->next());

        file_put_contents($filename, implode(PHP_EOL, $variables));

        $this->info(sprintf('Environment variables were written into %s', basename($filename)));

        return self::SUCCESS;
    }
}
