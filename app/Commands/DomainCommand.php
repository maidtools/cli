<?php

namespace App\Commands;

use App\Traits\InteractsWithMaidApi;
use Maid\Sdk\Exceptions\RequestRequiresClientIdException;
use Maid\Sdk\Maid;
use Maid\Sdk\Support\Manifest;
use GuzzleHttp\Exception\GuzzleException;
use LaravelZero\Framework\Commands\Command;

class DomainCommand extends Command
{
    use InteractsWithMaidApi;

    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'domain {domain}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Create a new domain';

    /**
     * @throws RequestRequiresClientIdException
     * @throws GuzzleException
     */
    public function handle(Maid $maid): int
    {
        $manifest = Manifest::get();

        $result = $maid
            ->withUserAccessToken()
            ->createDomain($manifest['project'], [
                'name' => $this->argument('domain'),
            ]);

        $result->dump();

        if ($result->success()) {
            $this->info('Domain creation initiated successfully.');
            $this->newLine();
            $this->info('Domains may take several minutes to finish propagating.');

            return self::SUCCESS;
        }

        return $this->failure($result, 'Cannot create a new domain.');
    }
}
