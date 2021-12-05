<?php

namespace App\Commands;

use App\Helper;
use App\K8s;
use LaravelZero\Framework\Commands\Command;

class K8sInitCommand extends Command
{
    protected $signature = 'k8s:init';

    protected $description = 'Create a k8s deployment and service for the current project';

    // this method will create a k8s.yaml file in the k8s directory
    // this file contains some basic information about the k8s deployment, like:
    // - the namespace
    // - if a imagePullSecrets is needed, and if so, which secret
    // - if a ConfigMap should be created, and if so, which configmap
    // - if a secret should be created, and if so, which secret
    // - if a service should be created, and if so, which service
    // - if a ingress should be created, and if so, which ingress
    public function handle(): int
    {
        $config = [];

        $config['namespace'] = $this->ask('What is the namespace for this project?', 'example-project');
        $imagePullSecrets = $this->ask('Do you need a imagePullSecrets? (y/n)', 'y');

        if ($imagePullSecrets) {
            $imagePullSecrets = $this->ask('What is the name of the imagePullSecrets?', 'registry-dev-own3d-tv');
            $config['imagePullSecrets'][] = [
                'name' => $imagePullSecrets
            ];
        }

        // ask if a configmap should be created, and if so, which configmap
        // ask if a secret should be created, and if so, which secret
        // save the config map and secret in k8s/config.yaml and k8s/secrets.yaml
        $configMap = $this->ask('Do you need a configMap? (y/n)', 'y');
        if ($configMap) {
            $configMap = $this->ask('What is the name of the configMap?', 'app-config');
            $config['configMap'][] = [
                'name' => $configMap
            ];

            // create a dummy configmap.yaml file
            K8s::yaml('config.yaml', [
                'apiVersion' => 'v1',
                'kind' => 'ConfigMap',
                'metadata' => [
                    'name' => $configMap,
                    'namespace' => $config['namespace']
                ],
                'data' => [
                    'KEY' => 'VALUE',
                ]
            ]);
        }

        $secret = $this->ask('Do you need a secret? (y/n)', 'y');
        if ($secret) {
            $secret = $this->ask('What is the name of the secret?', 'app-secret');
            $config['secret'][] = [
                'name' => $secret
            ];
            // create a dummy secrets.yaml file
            K8s::yaml('secrets.yaml', [
                'apiVersion' => 'v1',
                'kind' => 'Secret',
                'metadata' => [
                    'name' => $secret,
                    'namespace' => $config['namespace']
                ],
                'data' => [
                    'KEY' => 'VALUE',
                ]
            ]);
        }

        // create a envFrom for the configmap and secret and save it in config

        if (isset($config['configMap'])) {
            $config['envFrom'][] = [
                'configMapRef' => [
                    'name' => $config['configMap'][0]['name'],
                    'optional' => false,
                ]
            ];
        }
        if (isset($config['secret'])) {
            $config['envFrom'][] = [
                'secretRef' => [
                    'name' => $config['secret'][0]['name'],
                    'optional' => false,
                ]
            ];
        }

        K8s::yaml('k8s.yaml', $config);

        // create k8s/.gitignore file
        file_put_contents(K8s::base('.gitignore'), implode(PHP_EOL, [
            '.kube',
            'secrets.yaml',
            '/deployment.yaml',
            '/service.yaml',
        ]));

        // ask to create a service account
        $serviceAccount = $this->ask('Do you need a service account? (y/n)', 'y');

        // ask for a name and call the k8s:sa command
        if ($serviceAccount) {
            $this->call('k8s:sa', [
                '--kubectl' => true,
            ]);
        }

        // ask to create a template
        $template = $this->ask('Do you need a template? (y/n)', 'y');
        if ($template) {
            $this->call('k8s:template', [
                '--runtime' => true,
            ]);
        }

        // ask to create a ingress
        $ingress = $this->ask('Do you need a ingress? (y/n)', 'y');
        if($ingress) {
            $this->call('k8s:ingress');
        }

        return 0;
    }
}
