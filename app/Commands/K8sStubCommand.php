<?php

namespace App\Commands;

use App\K8s;
use LaravelZero\Framework\Commands\Command;
use mbaynton\CliEditorLauncher;
use Symfony\Component\Yaml\Yaml;

class K8sStubCommand extends Command
{
    protected $signature = 'k8s:stub {name}';

    protected $description = 'Copy a stubbed k8s files to the k8s directory';

    public function handle(): int
    {
        $name = $this->argument('name');

        $k8s = new K8s();

        if($k8s->copyStub($this, $name)) {
            $this->info("Stubbed k8s files copied to k8s directory");
        } else {
            $this->error("Failed to copy stubbed k8s files to k8s directory");
        }

        return 0;
    }
}
