<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Process\Process;

class PluginCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'plugin
                                {name : The plugin name}
                                {--remove : Removes the plugin instead of installing it}
                                {--dry-run : Simulate the command without actually doing anything}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Install optional plugins';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $pluginName = $this->argument('name');

        $pluginDirectory = sprintf(
            str_replace('/', DIRECTORY_SEPARATOR, '%s/vendor/ghostzero/maid'),
            exec('composer -n config --global home')
        );

        if (!file_exists($pluginDirectory)) {
            $this->warn(sprintf('The directory %s does not exists.', $pluginDirectory));
        }

        $this->info(sprintf(
            '%s of <comment>%s</comment>, this can take a while...',
            $this->option('remove') ? 'Uninstallation' : 'Installation',
            $pluginName
        ));

        $operation = $this->option('remove') ? 'remove' : 'require';
        $command = ['composer', $operation, $pluginName, '--ansi', '--no-interaction'];

        if ($this->option('dry-run')) {
            $command[] = '--dry-run';
        }

        if (!$this->getOutput()->isVerbose()) {
            $command[] = '--quiet';
        }

        $process = new Process($command, $pluginDirectory);
        $process->setTimeout(null);
        $process->run(function ($type, $buffer) {
            $this->getOutput()->write($buffer);
        });

        if ($process->isSuccessful()) {
            $this->info(sprintf(
                'Plugin <comment>%s</comment> successfully %s',
                $pluginName,
                $this->option('remove') ? 'uninstalled' : 'installed'
            ));
        } else {
            $this->warn('The operation failed. Use `-v` for verbose output.');
        }

        return 0;
    }
}
