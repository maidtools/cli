<?php

namespace App\Commands;

use App\Exceptions\EmptyShareException;
use App\Exceptions\Exception;
use App\Services\Clipboard;
use App\Services\Uploader;
use Joli\JoliNotif\Notification;
use Joli\JoliNotif\NotifierFactory;
use LaravelZero\Framework\Commands\Command;

class ShareCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'share
                                {string? : A string to be uploaded}
                                {name? : Name of this file}
                                {--paste : Paste content from the clipboard}
                                {--plain : Uploads the secret key}
                                {--notify : Triggers a system notify}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @param Clipboard $clipboard
     * @return mixed
     */
    public function handle(Clipboard $clipboard, Uploader $uploader)
    {
        $name = $this->argument('name') ?? 'encrypted.txt';
        try {
            $contents = $this->getContents($clipboard);

            $link = $uploader->multipart([[
                'name' => 'files[]',
                'contents' => $contents[1],
                'filename' => 'file.txt'
            ]], $this->option('plain'))[0];

            $clipboard->setClipboard($link);
        } catch (Exception $exception) {
            $this->warn($exception);

            return 1;
        }

        if ($this->option('notify')) {
            $this->notify($link);
        }

        $this->info($link);

        return 0;
    }

    private function notify(string $link)
    {
        $notifier = NotifierFactory::create();

        // Create your notification
        $notification =
            (new Notification())
                ->setTitle('File uploaded!')
                ->setBody($link);

        $notifier->send($notification);
    }

    private function getContents(Clipboard $clipboard): ?array
    {
        if ($this->option('paste')) {
            return $clipboard->getClipboard();
        }

        $string = $this->argument('string');
        if (!empty($data = $string ?? file_get_contents('php://stdin'))) {
            return ['text/plain', $data];
        }

        throw new EmptyShareException('Could not get content from stdin.');
    }
}
