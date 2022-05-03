<?php

namespace App\Services;

use Exception;
use FilesystemIterator;
use GhostZero\Maid\Support\Manifest;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Process\Process;

class Build
{
    private string $cwd;
    private Command $command;
    private string $latestImageName;

    public function __construct(string $cwd, Command $command)
    {
        $this->cwd = $cwd;
        $this->command = $command;
    }

    /**
     * @throws Exception
     */
    public function build()
    {
        $start = microtime(true);

        $this->command->info('Validating manifest...');
        $this->validateManifest();

        $this->command->info('Copying application files...');
        $this->copyApplicationFiles();

        $this->command->info('Executing build commands...');
        $this->executingBuildCommands();

        $this->command->info('Building docker image...');
        $this->buildDockerImage();

        $this->command->info('Push docker image...');
        $this->pushDockerImage();

        $this->command->info('Cleanup build files...');
        $this->cleanupBuildDirectory();

        $duration = microtime(true) - $start;
        $this->command->info(sprintf('Build completed in %.2f seconds!', $duration));
    }

    /**
     * @throws Exception
     */
    private function validateManifest(): void
    {
        $environment = $this->command->argument('environment');
        $manifest = Manifest::get();

        if (!isset($manifest['name'])) {
            throw new Exception('The manifest file must contain a name.');
        }

        if ($manifest['name'] !== Str::slug($manifest['name'])) {
            throw new Exception('The manifest file name must be a valid slug.');
        }

        if ($environment !== Str::slug($environment)) {
            throw new Exception('The manifest file name must be a valid slug.');
        }
    }

    private function copyApplicationFiles(): void
    {
        $this->copyDirectory(getcwd(), $this->cwd . DIRECTORY_SEPARATOR . '.maid/build');
    }

    private function copyDirectory(string $source, string $target): void
    {
        if (is_dir($target)) {
            $this->deleteDirectory($target);
        }

        mkdir($target, 0777, true);

        /** @var RecursiveDirectoryIterator $iterator */
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            // ignore .maid directories
            if (str_contains($item, '.maid')) {
                continue;
            }

            if ($item->isDir()) {
                mkdir($target . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
            } else {
                copy($item, $target . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
            }
        }
    }

    private function cleanupBuildDirectory(): void
    {
        $this->deleteDirectory($this->cwd . DIRECTORY_SEPARATOR . '.maid');
    }

    private function deleteDirectory(string $target): void
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($target, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            $todo = ($file->isDir() ? 'rmdir' : 'unlink');
            $todo($file->getRealPath());
        }

        rmdir($target);
    }

    /**
     * @throws Exception
     */
    private function executingBuildCommands(): void
    {
        $environment = $this->command->argument('environment');
        $manifest = Manifest::get();
        $commands = $manifest['environments'][$environment]['build'] ?? [];

        foreach ($commands as $command) {
            $this->command->getOutput()->writeln('<info>Run command:</info> ' . $command);

            $process = Process::fromShellCommandline($command, $this->cwd . DIRECTORY_SEPARATOR . '.maid/build');
            $process->setTimeout(null);
            $process->run(function ($type, $buffer) {
                $this->command->getOutput()->write($buffer);
            });
        }
    }

    /**
     * @throws Exception
     */
    private function buildDockerImage()
    {
        $environment = $this->command->argument('environment');
        $manifest = Manifest::get();
        $revision = $this->getRevision();
        $this->latestImageName = sprintf(
            '%s/%s/%s:%s-%s',
            $this->getDockerRegistry(),
            $this->getNamespace(),
            $manifest['name'],
            $environment,
            $revision
        );

        $this->command->info(sprintf('Build docker image as %s', $this->latestImageName));

        // write Dockerfile from phar to the current working directory
        file_put_contents(
            $dockerfile = sprintf('%s/Dockerfile', $this->getCurrentWorkingDirectory()),
            file_get_contents(base_path('resources/build/container/Dockerfile'))
        );

        $command = array_merge([
            'docker', 'build',
            '-f', $dockerfile,
            '-t', $this->latestImageName, '.',
        ], $this->getBuildArgs());

        $process = new Process($command, $this->getCurrentWorkingDirectory());
        $process->setTimeout(null);
        $process->run(function ($type, $buffer) {
            $this->command->getOutput()->write($buffer);
        });
    }

    private function getRevision(): string
    {
        return $this->command->option('revision') ?? date('YmdHis');
    }

    private function getBuildArgs(): array
    {
        $args = [];

        foreach ($this->command->option('build-arg') as $arg) {
            $args[] = '--build-arg';
            $args[] = $arg;
        }

        return $args;
    }

    private function pushDockerImage()
    {
        // docker login bpkg.io -u $REGISTRY_USER -p $REGISTRY_PASS
        $loginProcess = new Process([
            'docker', 'login', $this->getDockerRegistry(),
            '-u', $this->getNamespace(),
            '-p', 'tpt8bBaWV8kkfdnPAn7E45vqEK6sXyhb',
        ]);
        $loginProcess->run(function ($type, $buffer) {
            $this->command->getOutput()->write($buffer);
        });

        $pushProcess = new Process([
            'docker', 'push', $this->latestImageName
        ]);
        $pushProcess->setTimeout(null);
        $pushProcess->run(function ($type, $buffer) {
            $this->command->getOutput()->write($buffer);
        });
    }

    private function getDockerRegistry(): string
    {
        return 'pkg.maid.sh';
    }

    private function getNamespace(): string
    {
        return Manifest::get('project');
    }

    private function getCurrentWorkingDirectory(): string
    {
        return $this->cwd . DIRECTORY_SEPARATOR . '.maid/build';
    }
}
