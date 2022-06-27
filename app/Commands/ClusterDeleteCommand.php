<?php

namespace App\Commands;

use App\Traits\InteractsWithMaidApi;
use Maid\Sdk\Exceptions\RequestRequiresClientIdException;
use Maid\Sdk\Maid;
use Maid\Sdk\Support\Manifest;
use GuzzleHttp\Exception\GuzzleException;
use LaravelZero\Framework\Commands\Command;

class ClusterDeleteCommand extends Command
{
    use InteractsWithMaidApi;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'cluster:delete {cluster}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Delete an cluster';

    /**
     * @throws RequestRequiresClientIdException
     * @throws GuzzleException
     */
    public function handle(Maid $maid): int
    {
        $result = $maid
            ->withUserAccessToken()
            ->deleteCluster($this->argument('cluster'));

        if ($result->success()) {
            $this->info('Cluster has been deleted.');

            return self::SUCCESS;
        }

        return $this->failure($result, 'Cluster cannot be deleted.');
    }
}
