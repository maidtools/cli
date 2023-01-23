<?php

namespace App\Commands;

use App\Exceptions\LoginRequiredException;
use App\Traits\InteractsWithMaidApi;
use Maid\Sdk\Exceptions\RequestRequiresClientIdException;
use Maid\Sdk\Maid;
use GuzzleHttp\Exception\GuzzleException;
use LaravelZero\Framework\Commands\Command;

class RecordDeleteCommand extends Command
{
    use InteractsWithMaidApi;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'record:delete {domain} {type} {name}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Delete a domain record';

    /**
     * @throws RequestRequiresClientIdException
     * @throws GuzzleException
     */
    public function handle(Maid $maid): int
    {
        try {
            $result = $maid
                ->withUserAccessToken()
                ->deleteDomainRecord(
                    $this->argument('domain'),
                    $this->argument('type'),
                    $this->argument('name')
                );
        } catch (LoginRequiredException $e) {
            return $this->loginRequired($e);
        }

        if ($result->success()) {
            $this->info('Domain record deletion initiated successfully.');
            $this->newLine();
            $this->info('The domain deletion process may take several minutes to propagating.');

            return self::SUCCESS;
        }

        return $this->failure($result, 'Cannot delete the domain record.');
    }
}
