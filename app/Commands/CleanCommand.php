<?php

namespace App\Commands;

use App\Helper;
use LaravelZero\Framework\Commands\Command;

class CleanCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'clean {vendor=vendor}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Clean all vendor folders';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(): int
    {
        [$vendor, $vendorFile] = Helper::getVendor($this->argument('vendor'));
        $cwd = getcwd();
        $ignored = $this->ignored($cwd);

        $scan = scandir($cwd);

        $directories = [];

        $cleaned = 0;
        foreach ($scan as $directory) {
            if (in_array($directory, $ignored)) {
                continue;
            }
            if (is_file($cwd . DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR . $vendorFile)) {
                $vendorDirectory = $cwd . DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR . $vendor;

                if (is_dir($vendorDirectory)) {
                    $this->info('Cleanup: ' . $cwd . DIRECTORY_SEPARATOR . $directory);
                    $directories[] = $vendorDirectory;
                }
            }
        }

        if (count($directories) > 0 && $this->confirm('Do you want to delete all directories?')) {
            foreach ($directories as $directory) {
                $cleaned += Helper::cleanDirectory($directory);
            }
        }

        $this->info(sprintf('A total of %s has been cleaned up!', Helper::formatFilesize($cleaned)));

        return 0;
    }

    private function ignored($cwd): array
    {
        $ignored = ['..', '.'];
        $ignoreFile = sprintf('%s%s.broomignore', $cwd, DIRECTORY_SEPARATOR);

        if (is_file($ignoreFile)) {
            $ignored = array_merge($ignored, array_filter(explode(PHP_EOL, file_get_contents($ignoreFile))));
        }

        return $ignored;
    }
}
