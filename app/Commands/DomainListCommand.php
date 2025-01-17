<?php

namespace App\Commands;

use App\Exceptions\LoginRequiredException;
use App\Traits\InteractsWithMaidApi;
use Maid\Sdk\Exceptions\RequestRequiresClientIdException;
use Maid\Sdk\Maid;
use Maid\Sdk\Support\Manifest;
use GuzzleHttp\Exception\GuzzleException;
use LaravelZero\Framework\Commands\Command;

class DomainListCommand extends Command
{
    use InteractsWithMaidApi;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'domain:list
                            {--fields=name,primary_ns,secondary_ns,verified_at : Fields to select}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'List the domains that belong to the current project';

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
                ->getDomains($manifest['project']);
        } catch (LoginRequiredException $e) {
            return $this->loginRequired($e);
        }

        if ($result->success()) {
            $this->resultAsTable($result, $this);

            return self::SUCCESS;
        }

        return $this->failure($result, 'Cannot list the domains that belong to the current project.');
    }
}
