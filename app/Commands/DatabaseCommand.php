<?php

namespace App\Commands;

use App\Traits\InteractsWithMaidApi;
use Maid\Sdk\Exceptions\RequestRequiresClientIdException;
use Maid\Sdk\Maid;
use Maid\Sdk\Support\Manifest;
use GuzzleHttp\Exception\GuzzleException;
use LaravelZero\Framework\Commands\Command;

class DatabaseCommand extends Command
{
    use InteractsWithMaidApi;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'database {name}
                            {--engine=mariadb:10.7-focal}
                            {--node=}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Create a new database';

    /**
     * @throws RequestRequiresClientIdException
     * @throws GuzzleException
     */
    public function handle(Maid $maid): int
    {
        $manifest = Manifest::get();

        $result = $maid
            ->withUserAccessToken()
            ->createDatabase($manifest['project'], [
                'name' => $this->argument('name'),
                'engine' => $this->option('engine'),
                'node' => $this->option('node'),
            ]);


        if ($result->success()) {
            $this->info('Database creation initiated successfully.');
            $this->newLine();
            $this->info('Databases may take several minutes to finish provisioning.');

            return self::SUCCESS;
        }

        return $this->failure($result, 'Cannot create a new database instance.');
    }
}
