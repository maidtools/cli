<?php

namespace App\Commands;

use App\Helper;
use LaravelZero\Framework\Commands\Command;

class IgnoreCommand extends Command
{
    protected $signature = 'ignore {directory?} {--remove}';

    protected $description = 'Modify the .maidignore file.';

    public function handle(): int
    {
        $value = $this->argument('directory');

        if ($value === null) {
            system('nano .maidignore > `tty`');
            return 0;
        }

        $ignored = Helper::ignored(null, []);

        if ($this->option('remove')) {
            $this->remove($ignored, $value);
        } else {
            $this->append($ignored, $value);
        }

        $ignored = array_unique(array_filter($ignored));

        file_put_contents(getcwd() . '/.maidignore', implode(PHP_EOL, $ignored) . PHP_EOL);

        return 0;
    }

    private function remove(array &$ignored, string $value)
    {
        if (($key = array_search($value, $ignored)) !== false) {
            unset($ignored[$key]);
        }
    }

    private function append(array &$ignored, string $value)
    {
        $ignored[] = $value;
    }
}
