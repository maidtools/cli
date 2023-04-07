<?php

namespace App\Commands;

use App\Exceptions\LoginRequiredException;
use App\Traits\InteractsWithMaidApi;
use Maid\Sdk\Exceptions\RequestRequiresClientIdException;
use Maid\Sdk\Maid;
use GuzzleHttp\Exception\GuzzleException;
use LaravelZero\Framework\Commands\Command;

class CacheDeleteCommand extends Command
{
    use InteractsWithMaidApi;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'cache:delete {cache}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Delete a cache';

    /**
     * @throws RequestRequiresClientIdException
     * @throws GuzzleException
     */
    public function handle(Maid $maid): int
    {
        try {
            $result = $maid
                ->withUserAccessToken()
                ->deleteCache($this->argument('cache'));
        } catch (LoginRequiredException $e) {
            return $this->loginRequired($e);
        }

        if ($result->success()) {
            $this->info('Cache deletion initiated successfully.');
            $this->newLine();
            $this->info('The cache deletion process may take several minutes to complete.');

            return self::SUCCESS;
        }

        return $this->failure($result, 'Cannot delete the cache instance.');
    }
}
