<?php

namespace App\Commands;

use App\K8s;
use LaravelZero\Framework\Commands\Command;
use mbaynton\CliEditorLauncher;
use Symfony\Component\Yaml\Yaml;

class K8sIngressCommand extends Command
{
    protected $signature = 'k8s:ingress';

    protected $description = 'Edit Kubernetes env resources and apply the changes';

    public function handle(): int
    {
        $k8s = K8s::config();

        $tls = [];
        $rules = [];
        // search all k8s/templates/$name/service.yaml files for port mappings using port 80
        $path = K8s::base('templates/*/service.yaml');
        foreach (glob($path) as $file) {
            $service = Yaml::parseFile($file);
            foreach ($service['spec']['ports'] as $port) {
                if ($port['port'] == 80) {
                    // ask which host you want to use for this service port
                    $host = $this->ask(
                        "What hostname should be used for {$service['metadata']['name']} port {$port['port']}?",
                        $service['metadata']['name'],
                    );

                    $rules[] = [
                        'host' => $host,
                        'http' => [
                            'paths' => [
                                [
                                    'path' => '/',
                                    'pathType' => 'Prefix',
                                    'backend' => [
                                        'service' => [
                                            'name' => $service['metadata']['name'],
                                            'port' => $port['port'],
                                        ]
                                    ]
                                ],
                            ],
                        ]
                    ];

                    $tls[] = [
                        'hosts' => [$host],
                        'secretName' => $service['metadata']['name'] . '-tls',
                    ];
                }
            }
        }

        // Create a new ingress resource
        K8s::yaml('ingress.yaml', [
            'apiVersion' => 'extensions/v1beta1',
            'kind' => 'Ingress',
            'metadata' => [
                'name' => 'app-ingress',
                'namespace' => $k8s['namespace'],
                'labels' => [
                    'app' => 'app-ingress',
                ],
            ],
            'spec' => [
                'tls' => $tls,
                'rules' => $rules,
            ],
        ]);

        return 0;
    }
}
