<?php

namespace App\Commands;

use App\Exceptions\Exception;
use App\Services\Build;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Throwable;

class BuildCommand extends Command
{
    protected $signature = 'build {environment=staging}
                {--asset-url= : The asset base URL}
                {--build-arg=* : Docker build argument}
                {--revision= : Docker image version}
                {--working-dir= : The working directory}
                {--no-push : Do not push the image to the registry}';

    protected $description = 'Build the project container';

    public function handle(): int
    {
        chdir($this->getCurrentWorkingDirectory());

        $build = new Build(getcwd(), $this);
        $this->registerTerminateHandler($build);

        try {
            $build->build();
        } catch (ProcessFailedException $exception) {
            $error = sprintf("The previous command \"%s\" failed.\n\nExit Code: %s (%s)\n\nWorking directory: %s",
                $exception->getProcess()->getCommandLine(),
                $exception->getProcess()->getExitCode(),
                $exception->getProcess()->getExitCodeText(),
                $exception->getProcess()->getWorkingDirectory()
            );

            $this->newLine();
            $this->error($error);

            $this->info('Cleanup build files...');
            $build->cleanupBuildDirectory();

            return self::FAILURE;
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            $this->info('Cleanup build files...');
            $build->cleanupBuildDirectory();

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function getCurrentWorkingDirectory(): string
    {
        if ($this->option('working-dir')) {
            if ($workingDirectory = realpath($this->option('working-dir'))) {
                $this->info(sprintf('Using working directory: <comment>%s</comment>', $workingDirectory));
                return $workingDirectory;
            } else {
                throw new Exception('The given working directory does not exist.');
            }
        }

        if ($workingDirectory = getcwd()) {
            return $workingDirectory;
        }

        throw new Exception('Unable to determine the current working directory.');
    }

    private function registerTerminateHandler(Build $build): void
    {
        if (!extension_loaded('pcntl')) {
            return;
        }

        // Register a handler for SIGINT (Ctrl+C)
        pcntl_signal(SIGINT, function () use ($build) {
            $this->info('Cleanup build files...');
            $build->cleanupBuildDirectory();
        });
    }
}
