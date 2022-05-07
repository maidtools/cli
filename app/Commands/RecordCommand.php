<?php

namespace App\Commands;

use App\Traits\InteractsWithMaidApi;
use GhostZero\Maid\Exceptions\RequestRequiresClientIdException;
use GhostZero\Maid\Maid;
use GhostZero\Maid\Support\Manifest;
use GuzzleHttp\Exception\GuzzleException;
use LaravelZero\Framework\Commands\Command;

class RecordCommand extends Command
{
    use InteractsWithMaidApi;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'record {domain} {type} {name} {content}
                            {--fields=id,name,type,content,ttl : Fields to select}
                            {--ttl=300 : Time to Live}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Create a new domain record for a given domain';

    /**
     * @throws RequestRequiresClientIdException
     * @throws GuzzleException
     */
    public function handle(Maid $maid): int
    {
        $result = $maid
            ->withUserAccessToken()
            ->createDomainRecord($this->argument('domain'), [
                'name' => $this->argument('name'),
                'type' => $this->argument('type'),
                'content' => $this->argument('content'),
                'ttl' => $this->option('ttl'),
            ]);

        if ($result->success()) {
            $this->info('Domain record creation initiated successfully.');
            $this->newLine();
            $this->info('Domain records may take several minutes to finish propagating.');

            return self::SUCCESS;
        }

        return $this->failure($result, 'Cannot create a new domain record.');
    }
}
