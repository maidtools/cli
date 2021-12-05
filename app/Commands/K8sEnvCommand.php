<?php

namespace App\Commands;

use App\K8s;
use LaravelZero\Framework\Commands\Command;
use mbaynton\CliEditorLauncher;
use Symfony\Component\Yaml\Yaml;

class K8sEnvCommand extends Command
{
    protected $signature = 'k8s:env {resource}';

    protected $description = 'Edit Kubernetes env resources and apply the changes';

    public function handle(): int
    {
        $originalText = $this->getResourceEnv($this->argument('resource'));

        $editor = new CliEditorLauncher();
        $result = $editor->editString($originalText, "# Make your changes above.\n# This is a test.\n");
        if ($result->isChanged()) {
            $this->info("Changes applied");
            $this->applyResourceEnv($this->argument('resource'), $result);
        } else {
            $this->info("No changes applied");
        }

        return 0;
    }

    // return the file path of the resource
    private function getResourceEnv(string $filename): string
    {
        $env = '';
        $filename = K8s::base($filename);

        if (file_exists($filename)) {
            $config = Yaml::parseFile($filename);

            if ($config['kind'] === 'Deployment') {
                $env = $this->arrayToEnv($config['spec']['template']['spec']['containers'][0]['env'] ?? []);
            } elseif ($config['kind'] === 'StatefulSet') {
                $env = $this->arrayToEnv($config['spec']['template']['spec']['containers'][0]['env'] ?? []);
            } elseif ($config['kind'] === 'ConfigMap') {
                $env = $this->arrayToEnv($config['data'] ?? []);
            } elseif ($config['kind'] === 'Secret') {
                $env = $this->arrayToEnv($config['data'] ?? [], true);
            }
        }

        return $env;
    }

    private function arrayToEnv(array $env, bool $opaque = false): string
    {
        $output = '';

        foreach ($env as $key => $value) {
            if ($opaque) {
                $value = base64_decode($value);
            }
            $this->info("$key=$value");
            $output .= "{$key}={$value}\n";
        }

        return $output;
    }

    private function envToArray(string $env, bool $opaque = false): array
    {
        $output = [];

        foreach (explode("\n", $env) as $line) {
            $line = trim($line);

            if (str_contains($line, '=')) {
                list($key, $value) = explode('=', $line);

                if ($opaque) {
                    $output[$key] = base64_encode($value);
                } else {
                    $output[$key] = $value;
                }
            }
        }

        return $output;
    }

    private function applyResourceEnv(string $name, string $result)
    {
        $filename = K8s::base($name);

        if (!file_exists($filename)) {
            $this->error("File not found: {$filename}");
            return;
        }

        $config = Yaml::parseFile($filename);

        if ($config['kind'] === 'Deployment') {
            $config['spec']['template']['spec']['containers'][0]['env'] = $this->envToArray($result);
        } elseif ($config['kind'] === 'StatefulSet') {
            $config['spec']['template']['spec']['containers'][0]['env'] = $this->envToArray($result);
        } elseif ($config['kind'] === 'ConfigMap') {
            $config['data'] = $this->envToArray($result);
        } elseif ($config['kind'] === 'Secret') {
            $config['data'] = $this->envToArray($result, true);
        }

        K8s::yaml($name, $config);
    }
}
