<?php

namespace App\Commands;

use App\K8s;
use LaravelZero\Framework\Commands\Command;

class K8sTemplateCommand extends Command
{
    protected $signature = 'k8s:template {--runtime : Creates and use a custom docker image}';

    protected $description = 'Create a k8s deployment and service for the current project';

    // create a k8s deployment and service for the current project
    // the files should be stored within k8s/templates/{service}/{service|deployment}.yaml
    public function handle(): int
    {
        $service = $this->ask('Service name', 'laravel');

        if ($this->option('runtime')) {
            $this->info('Creating k8s docker dockerfile');

            $image = $this->createRuntimeDockerfile($service);
        } else {
            $image = $this->ask('What is the image name?', 'registry.dev.own3d.tv/own3d/hello-world:${CICD_GIT_COMMIT}');
        }

        $replicas = (int)$this->ask('How many replicas?', '1');

        $this->info('Creating k8s deployment and service for ' . $service);

        $this->info('Creating k8s deployment');
        K8s::yaml(
            sprintf('templates/%s/deployment.yaml', $service),
            $this->createDeployment($service, $image, $replicas)
        );

        $this->info('Creating k8s service');
        K8s::yaml(
            sprintf('templates/%s/service.yaml', $service),
            $this->createService($service)
        );

        return 0;
    }

    private function createDeployment(string $service, $image, $replicas): array
    {
        $k8s = K8s::config();

        $deployment = [
            'apiVersion' => 'apps/v1',
            'kind' => 'Deployment',
            'metadata' => [
                'name' => $service,
                'namespace' => $k8s['namespace'],
                'labels' => [
                    'app' => $service,
                ],
            ],
            'spec' => [
                'replicas' => $replicas,
                'selector' => [
                    'matchLabels' => [
                        'app' => $service,
                    ],
                ],
                'template' => [
                    'metadata' => [
                        'labels' => [
                            'app' => $service,
                        ],
                    ],
                    'spec' => [
                        'containers' => [
                            [
                                'name' => $service,
                                'image' => $image,
                                'imagePullPolicy' => 'Always',
                                'ports' => [
                                    [
                                        'containerPort' => 80,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        if ($k8s['envFrom']) {
            $deployment['spec']['template']['spec']['containers'][0]['envFrom'] = $k8s['envFrom'];
        }

        if ($k8s['imagePullSecrets']) {
            $deployment['spec']['template']['spec']['imagePullSecrets'] = $k8s['imagePullSecrets'];
        }

        return $deployment;
    }

    private function createService(string $service): array
    {
        $k8s = K8s::config();

        return [
            'apiVersion' => 'v1',
            'kind' => 'Service',
            'metadata' => [
                'name' => $service,
                'namespace' => $k8s['namespace'],
                'labels' => [
                    'app' => $service,
                ],
            ],
            'spec' => [
                'type' => 'LoadBalancer',
                'ports' => [
                    [
                        'port' => 80,
                        'targetPort' => 80,
                    ],
                ],
                'selector' => [
                    'app' => $service,
                ],
            ],
        ];
    }

    private function createRuntimeDockerfile(string $service): string
    {
        $baseImage = $this->ask('What is the base image?', 'own3d/laravel-docker:8.0-octane');

        $k8s = K8s::config();
        $namespace = $k8s['namespace'];

        $image = $this->ask(
            'What is the image name?',
            sprintf('registry.dev.own3d.tv/%s/%s:${CICD_GIT_COMMIT}', $namespace, $service)
        );

        file_put_contents(
            K8s::ensureParentDirectoryExits(
                K8s::base(sprintf('runtimes/%s/Dockerfile', $service))
            ),
            implode(PHP_EOL, [
                'FROM own3d/laravel-docker:8.0-octane',
                sprintf('COPY k8s/runtimes/%s/php-overrides.ini /usr/local/etc/php/conf.d/overrides.ini', $service),
                '',
                'RUN apk add postgresql-dev --no-cache',
                '',
                'RUN docker-php-ext-install pgsql',
                'RUN docker-php-ext-install pdo_pgsql',
                '',
                sprintf('COPY k8s/runtimes/%s/start.sh /usr/local/bin/start', $service),
                '',
                'RUN chmod o+x /usr/local/bin/start',
                '',
                'COPY . /var/www/html',
                'RUN echo $CICD_GIT_COMMIT > /var/www/html/git_hash',
                'RUN chown -R www-data:www-data /var/www/html',
                '',
                'EXPOSE 80',
                '',
                'CMD ["/usr/local/bin/start"]',
            ])
        );

        file_put_contents(
            K8s::ensureParentDirectoryExits(
                K8s::base(sprintf('runtimes/%s/php-overrides.ini', $service))
            ),
            implode(PHP_EOL, [
                'memory_limit=256M',
                'zend.assertions=-1',
                'date.timezone=UTC',
            ])
        );

        file_put_contents(
            K8s::ensureParentDirectoryExits(
                K8s::base(sprintf('runtimes/%s/start.sh', $service))
            ),
            implode(PHP_EOL, [
                '#!/bin/bash',
                '',
                'set -e',
                '',
                sprintf('echo "Starting %s..."', $service),
                'ENV=${APP_ENV:-production}',
                'MAX_REQUESTS=${OCTANE_MAX_REQUESTS:-250}',
                '',
                'php /var/www/html/artisan octane:start --max-requests=${MAX_REQUESTS} --port=80 --host=0.0.0.0',
            ])
        );

        return $image;
    }
}
