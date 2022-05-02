<?php

namespace App\Commands;

use App\Traits\InteractsWithMaidApi;
use GhostZero\Maid\Exceptions\RequestRequiresClientIdException;
use GhostZero\Maid\Maid;
use GhostZero\Maid\Support\Manifest;
use GuzzleHttp\Exception\GuzzleException;
use LaravelZero\Framework\Commands\Command;

class CacheListCommand extends Command
{
    use InteractsWithMaidApi;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'cache:list
                            {--fields=id,name,password,port : Fields to select}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'List the caches that belong to the current project';

    /**
     * @throws RequestRequiresClientIdException
     * @throws GuzzleException
     */
    public function handle(Maid $maid): int
    {
        $manifest = Manifest::get();

        $result = $maid
            ->withUserAccessToken()
            ->getCaches($manifest['project']);

        if ($result->success()) {
            $this->resultAsTable($result, $this);

            return self::SUCCESS;
        }

        return $this->failure($result, 'Cannot list the caches that belong to the current project.');
    }
}
