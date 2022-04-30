<?php

namespace App\Commands;

use App\Maid;
use Exception;
use LaravelZero\Framework\Commands\Command;

class RedeployCommand extends Command
{
    protected $signature = 'redeploy {environment=staging}
                {--revision=latest : The revision to deploy}
                {--f|force : Force redeployment}';

    protected $description = 'Redeploy the application to a specific revision';

    public function handle(Maid $maid): int
    {
        $this->info(sprintf(
            'Deploying %s to revision %s',
            $this->argument('environment'),
            $this->option('revision')
        ));

        try {
            $maid
                ->withToken(Maid::getAccessToken())
                ->deploy([
                    'manifest' => Maid::getManifest(),
                    'environment' => $this->argument('environment'),
                    'revision' => $this->option('revision'),
                ]);

            $this->info('Deployment successful!');
        } catch (Exception $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
