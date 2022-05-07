<?php

namespace App\Commands;

use App\Traits\InteractsWithMaidApi;
use GhostZero\Maid\Exceptions\RequestRequiresClientIdException;
use GhostZero\Maid\Maid;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class ClusterImportCommand extends Command
{
    use InteractsWithMaidApi;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'cluster:import {provider} {name}
            {--api-key= : API-Key}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Import an existing cluster from a cloud provider';

    /**
     * @throws RequestRequiresClientIdException
     * @throws GuzzleException
     */
    public function handle(Maid $maid): int
    {
        $result = $maid
            ->withUserAccessToken()
            ->importCluster([
                'provider' => $this->argument('provider'),
                'name' => $this->argument('name'),
                'api_key' => $this->option('api-key'),
            ]);


        $result->dump();

        if ($result->success()) {
            $this->info('Cluster import initiated successfully.');

            return self::SUCCESS;
        }

        return $this->failure($result, 'Cannot create a new cluster.');
    }

    /**
     * Define the command's schedule.
     *
     * @param Schedule $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }
}
