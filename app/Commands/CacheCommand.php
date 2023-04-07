<?php

namespace App\Commands;

use App\Exceptions\LoginRequiredException;
use App\Traits\InteractsWithMaidApi;
use Maid\Sdk\Exceptions\RequestRequiresClientIdException;
use Maid\Sdk\Maid;
use Maid\Sdk\Support\Manifest;
use GuzzleHttp\Exception\GuzzleException;
use LaravelZero\Framework\Commands\Command;


class CacheCommand extends Command
{
    use InteractsWithMaidApi;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'cache {name}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Create a new cache';

    /**
     * @throws RequestRequiresClientIdException
     * @throws GuzzleException
     */
    public function handle(Maid $maid): int
    {
        $manifest = Manifest::get();

        try {
            $result = $maid
                ->withUserAccessToken()
                ->createCache($manifest['project'], [
                    'name' => $this->argument('name'),
                ]);
        } catch (LoginRequiredException $e) {
            return $this->loginRequired($e);
        }


        if ($result->success()) {
            $this->info('Cache creation initiated successfully.');
            $this->newLine();
            $this->info('Caches may take several minutes to finish provisioning.');

            return self::SUCCESS;
        }

        return $this->failure($result, 'Cannot create a new cache instance.');
    }
}
