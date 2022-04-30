<?php

namespace App\Commands;

use App\Maid;
use Exception;
use LaravelZero\Framework\Commands\Command;

class RollbackCommand extends Command
{
    protected $signature = 'rollback
                {--f|force : Force the rollback without asking for confirmation}';

    protected $description = 'Rollback to the previous version';

    public function handle(Maid $maid): int
    {
        if (!$this->option('force')) {
            if (!$this->confirm('Are you sure you want to rollback?')) {
                return self::FAILURE;
            }
        }

        $this->info('Rolling back to previous version...');

        try {
            $maid
                ->withToken(Maid::getAccessToken())
                ->rollback(Maid::getManifest('name'));

            $this->info('Rollback successful!');
        } catch (Exception $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
