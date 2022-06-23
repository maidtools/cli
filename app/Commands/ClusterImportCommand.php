<?php

namespace App\Commands;

use App\Traits\InteractsWithMaidApi;
use GhostZero\Maid\Exceptions\RequestRequiresClientIdException;
use GhostZero\Maid\Maid;
use GuzzleHttp\Exception\GuzzleException;
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
                            {--api-key= : API key from the cloud provider}
                            {--ingress-controller=nginx : Specify which ingress controller should be used (nginx or traefik)}
                            {--allow-install-or-upgrade=* : Allows to install or upgrade an ingress controller if it is missing}
                            {--kubeconfig= : Allows to install or upgrade an ingress controller if it is missing}';

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
                'ingress_controller' => $this->option('ingress-controller'),
                'install_or_upgrade' => $this->option('allow-install-or-upgrade'),
                'kubeconfig' => $this->getFileContents($this->option('kubeconfig')),
            ]);


        $result->dump();

        if ($result->success()) {
            $this->info('Cluster import initiated successfully.');

            return self::SUCCESS;
        }

        return $this->failure($result, 'Cannot create a new cluster.');
    }

    private function getFileContents(?string $filename): ?string
    {
        if (!empty($filename) && file_exists($filename)) {
            return file_get_contents($filename);
        }

        return null;
    }
}
