<?php

namespace App\Commands;

use App\Helper;
use Composer\Util\Filesystem;
use LaravelZero\Framework\Commands\Command;

class CleanCommand extends Command
{
    protected $signature = 'clean
                                {vendor=npm}
                                {--dry-run} : Simulate the command without deletion directories.
                                {--force} : Ignores the confirm during the deletion process.';

    protected $description = 'Clean vendor folders.';

    private Filesystem $filesystem;

    public function __construct()
    {
        parent::__construct();

        $this->filesystem = new Filesystem();
    }

    public function handle(): int
    {
        $vendors = explode(',', $this->argument('vendor'));

        foreach ($vendors as $vendor) {
            [$vendor, $vendorFile] = Helper::getVendor($vendor);
            $statusCode = $this->cleanupVendor($vendor, $vendorFile);
            if ($statusCode > 0) {
                return $statusCode;
            }
        }

        return 0;
    }

    private function cleanupVendor(string $vendor, string $vendorFile): int
    {
        $cwd = getcwd();
        $ignored = Helper::ignored($cwd);

        $scan = scandir($cwd);
        $bar = $this->output->createProgressBar(count($scan));
        $this->info("Checking for `{$vendor}` directories, this may take a while...");

        $directories = [];

        $possible = 0;
        $rows = [];
        foreach ($scan as $directory) {
            $bar->advance();
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

        $bar->finish();
        $this->output->newLine();

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
