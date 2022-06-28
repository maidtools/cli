<?php

namespace App\Commands;

use App\Traits\InteractsWithMaidApi;
use Maid\Sdk\Exceptions\RequestRequiresClientIdException;
use Maid\Sdk\Maid;
use Maid\Sdk\Support\Manifest;
use GuzzleHttp\Exception\GuzzleException;
use LaravelZero\Framework\Commands\Command;

class RecordIngressCommand extends Command
{
    use InteractsWithMaidApi;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'record:ingress {domain} {name}
                            {--fields=id,name,type,content,ttl : Fields to select}
                            {--ttl=300 : Time to Live}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Create a new ingress domain record for a given domain';

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
                'type' => 'CNAME',
                'content' => sprintf('ingress.%s', $this->argument('domain')),
                'ttl' => $this->option('ttl'),
            ]);

        if ($result->success()) {
            $this->info('Ingress domain record creation initiated successfully.');
            $this->newLine();
            $this->info('Ingress domain records may take several minutes to finish propagating.');

            return self::SUCCESS;
        }

        return $this->failure($result, 'Cannot create a new ingress domain record.');
    }
}
