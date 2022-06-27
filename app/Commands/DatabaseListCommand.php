<?php

namespace App\Commands;

use App\Traits\InteractsWithMaidApi;
use Maid\Sdk\Exceptions\RequestRequiresClientIdException;
use Maid\Sdk\Maid;
use Maid\Sdk\Support\Manifest;
use GuzzleHttp\Exception\GuzzleException;
use LaravelZero\Framework\Commands\Command;

class DatabaseListCommand extends Command
{
    use InteractsWithMaidApi;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'database:list
                            {--fields=id,name,engine,database,username,password,port : Fields to select}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'List the databases that belong to the current project';

    /**
     * @throws RequestRequiresClientIdException
     * @throws GuzzleException
     */
    public function handle(Maid $maid): int
    {
        $manifest = Manifest::get();

        $result = $maid
            ->withUserAccessToken()
            ->getDatabases($manifest['project']);

        if ($result->success()) {
            $this->resultAsTable($result, $this);

            return self::SUCCESS;
        }

        return $this->failure($result, 'Cannot list the databases that belong to the current project.');
    }
}
