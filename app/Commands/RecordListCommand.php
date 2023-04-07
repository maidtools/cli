<?php

namespace App\Commands;

use App\Exceptions\LoginRequiredException;
use App\Traits\InteractsWithMaidApi;
use Maid\Sdk\Exceptions\RequestRequiresClientIdException;
use Maid\Sdk\Maid;
use GuzzleHttp\Exception\GuzzleException;
use LaravelZero\Framework\Commands\Command;

class RecordListCommand extends Command
{
    use InteractsWithMaidApi;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'record:list {domain}
                            {--fields=name,type,content,ttl : Fields to select}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'List the records that belong to the given domain';

    /**
     * @throws RequestRequiresClientIdException
     * @throws GuzzleException
     */
    public function handle(Maid $maid): int
    {
        try {
            $result = $maid
                ->withUserAccessToken()
                ->getDomainRecords($this->argument('domain'));
        } catch (LoginRequiredException $e) {
            return $this->loginRequired($e);
        }

        if ($result->success()) {
            $this->resultAsTable($result, $this);

            return self::SUCCESS;
        }

        return $this->failure($result, 'Cannot list the domains that belong to the given domain.');
    }
}
