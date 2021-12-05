<?php

namespace App\Commands;

use App\K8s;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;

class K8sSACommand extends Command
{
    protected $signature = 'k8s:sa {--kubectl : Create a kubectl config file for the service account}';

    protected $description = 'Create a Kubernetes Service Account';

    public function handle(): int
    {
        $k8s = K8s::config();
        $default = strtolower('sa-' . Str::random(8));
        $name = $this->ask('Name of the Service Account', $default);

        // create service account
        $this->info('Creating Service Account...');

        K8s::yaml('sa.yaml', [
            'apiVersion' => 'v1',
            'kind' => 'ServiceAccount',
            'metadata' => [
                'name' => $name,
                'namespace' => $k8s['namespace'],
            ],
        ]);

        // create role with the following rules:
        // - api groups: ["*"]
        // - resources: ["*"]
        // - verbs: ["*"]
        $this->info('Creating Role...');
        K8s::yaml('role.yaml', [
            'apiVersion' => 'rbac.authorization.k8s.io/v1',
            'kind' => 'Role',
            'metadata' => [
                'name' => $name,
                'namespace' => $k8s['namespace'],
            ],
            'rules' => [
                [
                    'apiGroups' => ['*'],
                    'resources' => ['*'],
                    'verbs' => ['*'],
                ],
            ],
        ]);

        // create role binding with the following rules:
        // - role: $name
        // - service account: $name
        $this->info('Creating Role Binding...');
        K8s::yaml('role-binding.yaml', [
            'apiVersion' => 'rbac.authorization.k8s.io/v1',
            'kind' => 'RoleBinding',
            'metadata' => [
                'name' => $name,
                'namespace' => $k8s['namespace'],
            ],
            'roleRef' => [
                'apiGroup' => 'rbac.authorization.k8s.io',
                'kind' => 'Role',
                'name' => $name,
            ],
            'subjects' => [
                [
                    'kind' => 'ServiceAccount',
                    'name' => $name,
                    'namespace' => $k8s['namespace'],
                ],
            ],
        ]);

        // merge all the creates files above into $name.yaml
        $this->info('Creating Service Account...');

        $helperFiles = [
            'sa.yaml',
            'role.yaml',
            'role-binding.yaml'
        ];

        K8s::merge(sprintf('%s.yaml', $name), $helperFiles);
        K8s::cleanup($helperFiles);

        $this->info('Service Account created successfully!');

        if($this->option('kubectl')) {
            $this->info('Creating kubectl config file...');

            $server = $this->ask('What is the server address?', 'https://kubernetes.default.svc');
            $token = $this->secret('What is the token?');

            K8s::yaml('.kube/config', [
                'apiVersion' => 'v1',
                'kind' => 'Config',
                'clusters' => [
                    [
                        'cluster' => [
                            'server' => $server,
                        ],
                        'name' => 'default',
                    ],
                ],
                'users' => [
                    [
                        'name' => 'default',
                        'user' => [
                            'token' => $token,
                        ],
                    ],
                ],
                'contexts' => [
                    [
                        'context' => [
                            'cluster' => 'default',
                            'user' => $name,
                        ],
                        'name' => 'default',
                    ],
                ],
                'current-context' => 'default',
            ]);
            $this->info('kubectl config file created successfully!');
        }

        return 0;
    }
}
