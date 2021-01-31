<?php

namespace App\Commands;

use App\Helper;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class IgnoreCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'ignore {directory} {--remove}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Modify the .maidignore file.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(): int
    {
        $ignored = Helper::ignored(null, []);

        $value = $this->argument('directory');

        if ($this->option('remove')) {
            $this->remove($ignored, $value);
        } else {
            $this->append($ignored, $value);
        }

        $ignored = array_unique(array_filter($ignored));

        file_put_contents(getcwd() . '/.maidignore', implode(PHP_EOL, $ignored));

        return 0;
    }

    /**
     * Define the command's schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
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
