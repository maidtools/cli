<?php

namespace App\Commands;

use App\Traits\InteractsWithMaidApi;
use Maid\Sdk\Exceptions\RequestRequiresClientIdException;
use Maid\Sdk\Maid;
use Maid\Sdk\Support\Manifest;
use GuzzleHttp\Exception\GuzzleException;
use LaravelZero\Framework\Commands\Command;

class DatabaseDeleteCommand extends Command
{
    use InteractsWithMaidApi;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'database:delete {database}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Delete a database';

    /**
     * @throws RequestRequiresClientIdException
     * @throws GuzzleException
     */
    public function handle(Maid $maid): int
    {
        $result = $maid
            ->withUserAccessToken()
            ->deleteDatabase($this->argument('database'));

        if ($result->success()) {
            $this->info('Database deletion initiated successfully.');
            $this->newLine();
            $this->info('The database deletion process may take several minutes to complete.');

            return self::SUCCESS;
        }

        return $this->failure($result, 'Cannot delete the database instance.');
    }
}
