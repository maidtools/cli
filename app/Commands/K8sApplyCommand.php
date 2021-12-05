<?php

namespace App\Commands;

use App\Helper;
use App\K8s;
use LaravelZero\Framework\Commands\Command;

class K8sApplyCommand extends Command
{
    protected $signature = 'k8s:apply {--force}';

    protected $description = 'Apply k8s resources';

    public function handle(): int
    {
        $force = $this->option('force');

        $this->info('Applying k8s resources...');

        // merge all files from k8s/templates/{service}/{service|deployment}.yaml into k8s/service.yaml and deployment.yaml
        // join yaml files with a --- separator
        $this->info('Merging k8s resources...');

        $serviceFiles = glob(K8s::base('templates/*/service.yml'));
        $deploymentFiles = glob(K8s::base('templates/*/deployment.yml'));

        $serviceContent = Helper::mergeYamlFiles($serviceFiles);
        $deploymentContent = Helper::mergeYamlFiles($deploymentFiles);

        $serviceContent = $this->replacePlaceholders($serviceContent);
        $deploymentContent = $this->replacePlaceholders($deploymentContent);

        $serviceFile = K8s::base('service.yml');
        $deploymentFile = K8s::base('deployment.yml');

        $this->info('Writing ' . $serviceFile . '...');
        file_put_contents($serviceFile, $serviceContent);
        $this->info('Writing ' . $deploymentFile . '...');
        file_put_contents($deploymentFile, $deploymentContent);

        $this->info('Applying k8s resources...');

        $args = [
            sprintf('-f %s', $serviceFile),
            sprintf('-f %s', $deploymentFile),
        ];

        if (!empty($_SERVER['KUBE_CONFIG_DATA'])) {
            $tmpFile = tempnam(sys_get_temp_dir(), 'k8s');
            file_put_contents($tmpFile, $_SERVER['KUBE_CONFIG_DATA']);
            $args[] = sprintf('--kubeconfig=%s', $tmpFile);
        }

        $this->info('Running kubectl apply...');

        // check if kubectl is available, if not install it
        if (!Helper::isCommandAvailable('kubectl')) {
            $this->info('kubectl not found, installing...');
            shell_exec('curl -LO "https://dl.k8s.io/release/$(curl -L -s https://dl.k8s.io/release/stable.txt)/bin/linux/amd64/kubectl"');
            shell_exec('chmod +x kubectl');
        }

        $isSuccessful = K8s::runCommand(
            sprintf('kubectl apply %s', implode(' ', $args)),
            $this
        );

        if (!$isSuccessful) {
            $this->error('Failed to apply k8s resources');
            return 1;
        }

        return 0;
    }

    private function replacePlaceholders(string $content): string
    {
        foreach ($_SERVER as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            $content = str_replace('${' . $key . '}', $value, $content);
        }

        return $content;
    }
}
