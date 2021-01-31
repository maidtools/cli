<?php

namespace App\Commands;

use App\Helper;
use Composer\Util\Filesystem;
use LaravelZero\Framework\Commands\Command;

class CleanupCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'cleanup
                                {vendor=vendor}
                                {--dry-run} : Simulate the command without deletion directories.
                                {--force} : Ignores the confirm during the deletion process.';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Clean vendor folders.';

    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct()
    {
        parent::__construct();

        $this->filesystem = new Filesystem();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(): int
    {
        [$vendor, $vendorFile] = Helper::getVendor($this->argument('vendor'));
        $cwd = getcwd();
        $ignored = Helper::ignored($cwd);

        $scan = scandir($cwd);

        $directories = [];

        $possible = 0;
        $rows = [];
        foreach ($scan as $directory) {
            if (in_array($directory, $ignored)) {
                continue;
            }
            if (is_file($cwd . DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR . $vendorFile)) {
                $vendorDirectory = $cwd . DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR . $vendor;

                if (is_dir($vendorDirectory)) {
                    $scanned = $this->filesystem->size($directory);
                    $possible += $scanned;
                    $rows[] = [$vendorDirectory, Helper::formatFilesize($scanned)];
                    $directories[] = $vendorDirectory;
                }
            }
        }

        if (count($directories) <= 0) {
            $this->info('There is nothing to do.');

            return 0;
        }

        $this->table(['Directory', 'Size'], $rows);

        if (!$force = $this->option('force')) {
            $this->info(sprintf('You can save %s of disk space.', Helper::formatFilesize($possible)));
        }

        if ($dryRun = $this->option('dry-run')) {
            $this->warn('Dry-run is enabled!');
        }

        if (!$dryRun && ($force || $this->confirm('Do you want to delete all directories?'))) {
            foreach ($directories as $directory) {
                $this->filesystem->remove($directory);
            }

            $this->info(sprintf('A total of %s has been cleaned up!', Helper::formatFilesize($possible)));
        } else {
            $this->info('Cleanup canceled.');
        }

        return 0;
    }
}
