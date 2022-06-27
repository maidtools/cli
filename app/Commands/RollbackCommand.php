<?php

namespace App\Commands;

use App\Traits\InteractsWithMaidApi;
use Maid\Sdk\Exceptions\RequestRequiresClientIdException;
use Maid\Sdk\Maid;
use Maid\Sdk\Support\Manifest;
use GuzzleHttp\Exception\GuzzleException;
use LaravelZero\Framework\Commands\Command;

class RollbackCommand extends Command
{
    use InteractsWithMaidApi;

    protected $signature = 'rollback
                {--e|environment=staging : Rollback on a specific branch}
                {--i|id= : Rollback on a specific deployment}
                {--f|force : Force the rollback without asking for confirmation}';

    protected $description = 'Rollback an environment to a previous deployment';

    /**
     * @throws RequestRequiresClientIdException
     * @throws GuzzleException
     */
    public function handle(Maid $maid): int
    {
        $manifest = Manifest::get();

        if (!$this->option('force')) {
            if (!$this->confirm('Are you sure you want to rollback?')) {
                return self::FAILURE;
            }
        }

        $this->info('Rolling back to previous version...');

        if ($this->option('id')) {
            $result = $maid
                ->withUserAccessToken()
                ->rollbackDeployment(
                    $manifest['project'],
                    $this->option('id'),
                );
        } else {
            $result = $maid
                ->withUserAccessToken()
                ->rollbackLatestDeployment(
                    $manifest['project'],
                    [
                        'environment' => $this->option('environment'),
                    ]
                );
        }

        if ($result->success()) {
            $this->info('Rollback successful!');

            return self::SUCCESS;
        }

        return $this->failure($result, 'Rollback failed.');
    }
}
