<?php

namespace App\Commands;

use App\Exceptions\LoginRequiredException;
use App\Traits\InteractsWithMaidApi;
use Maid\Sdk\Exceptions\RequestRequiresClientIdException;
use Maid\Sdk\Maid;
use Maid\Sdk\Support\Manifest;
use GuzzleHttp\Exception\GuzzleException;
use LaravelZero\Framework\Commands\Command;

class DeployCommand extends Command
{
    use InteractsWithMaidApi;

    protected $signature = 'deploy {environment=staging}
                {--asset-url= : The asset base URL}
                {--build-arg=* : Docker build argument}
                {--revision= : Docker image version}';

    protected $description = 'Deploy an environment';

    /**
     * @throws RequestRequiresClientIdException
     * @throws GuzzleException
     */
    public function handle(Maid $maid): int
    {
        $manifest = Manifest::get();

        if (empty($manifest['environments'][$this->argument('environment')])) {
            $this->warn(sprintf('Environment %s does not exist.', $this->argument('environment')));

            return self::FAILURE;
        }

        $this->info(sprintf('Building the application for %s...', $this->argument('environment')));

        $result = $this->call('build', [
            'environment' => $this->argument('environment'),
            '--asset-url' => $this->option('asset-url'),
            '--build-arg' => $this->option('build-arg'),
            '--revision' => $revision = $this->getRevision(),
        ]);

        if ($result !== self::SUCCESS) {
            $this->warn('Build failed!');

            return $result;
        }

        $this->info('Deploying the application...');

        try {
            $result = $maid
                ->withUserAccessToken()
                ->createDeployment($manifest['project'], [
                    'environment' => $this->argument('environment'),
                    'manifest' => $manifest,
                    'revision' => $revision,
                ]);
        } catch (LoginRequiredException $e) {
            return $this->loginRequired($e);
        }

        if (!$result->success()) {
            return $this->failure($result, 'Deployment failed!');
        }

        $this->info(sprintf(
            'Deployment is live with revision %s.',
            $revision,
        ));

        $this->info(sprintf('You can rollback with "maid rollback -e %s".', $this->argument('environment')));

        return self::SUCCESS;
    }

    private function getRevision(): string
    {
        if ($revision = $this->option('revision')) {
            return $revision;
        }

        return time();
    }
}
