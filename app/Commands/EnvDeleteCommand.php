<?php

namespace App\Commands;

use App\Traits\InteractsWithMaidApi;
use GhostZero\Maid\Exceptions\RequestRequiresClientIdException;
use GhostZero\Maid\Maid;
use GhostZero\Maid\Support\Manifest;
use GuzzleHttp\Exception\GuzzleException;
use LaravelZero\Framework\Commands\Command;

class EnvDeleteCommand extends Command
{
    use InteractsWithMaidApi;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'env:delete {environment=production}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Delete an environment';

    /**
     * @throws RequestRequiresClientIdException
     * @throws GuzzleException
     */
    public function handle(Maid $maid): int
    {
        $manifest = Manifest::get();

        $result = $maid
            ->withUserAccessToken()
            ->flushEnvironmentVariables($manifest['project'], $this->argument('environment'));

        if ($result->success()) {
            $this->info('Environment has been flushed.');

            return self::SUCCESS;
        }

        return $this->failure($result, 'Environment cannot be flushed.');
    }
}
