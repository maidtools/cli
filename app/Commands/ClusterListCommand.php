<?php

namespace App\Commands;

use App\Exceptions\LoginRequiredException;
use App\Traits\InteractsWithMaidApi;
use Maid\Sdk\Exceptions\RequestRequiresClientIdException;
use Maid\Sdk\Maid;
use GuzzleHttp\Exception\GuzzleException;
use LaravelZero\Framework\Commands\Command;

class ClusterListCommand extends Command
{
    use InteractsWithMaidApi;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'cluster:list
                            {--fields=id,name,context,engine,ingress_controller,cloud_provider_id,project_id,user_id : Fields to select}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'List the clusters that belong to the current user';

    /**
     * @throws RequestRequiresClientIdException
     * @throws GuzzleException
     */
    public function handle(Maid $maid): int
    {
        try {
            $result = $maid
                ->withUserAccessToken()
                ->getClusters();
        } catch (LoginRequiredException $e) {
            return $this->loginRequired($e);
        }

        if ($result->success()) {
            $this->resultAsTable($result, $this);

            return self::SUCCESS;
        }

        return $this->failure($result, 'Cannot list the clusters that belong to the current user.');
    }
}
