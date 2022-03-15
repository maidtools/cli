<?php

namespace App\Commands;

use App\Helper;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use LaravelZero\Framework\Commands\Command;
use SodiumException;

class UploadCommand extends Command
{
    protected $signature = 'upload {file} {--e|encrypt : Encrypt the file before uploading}';

    protected $description = 'Upload an file to the server';

    public function handle(): int
    {
        $file = $this->argument('file');

        if (!file_exists($file)) {
            $this->error('File not found');

            return 1;
        }

        $this->info($this->multipart([[
            'name' => 'file',
            'contents' => file_get_contents($file),
            'filename' => basename($file),
        ]], $this->option('encrypt')));

        return 0;
    }

    /**
     * @throws GuzzleException
     * @throws Exception
     */
    public function multipart(array $multipart, bool $encrypt): string
    {
        if ($encrypt) {
            $key = random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
            $multipart = $this->encryptMultipart($multipart, $key);
        }

        $client = new Client([
            'base_uri' => 'https://api.maid.sh/v1/',
        ]);

        $bar = $this->output->createProgressBar(100);

        $result = $client->request('POST', 'files', [
            RequestOptions::MULTIPART => $multipart,
            RequestOptions::HEADERS => [
                'Accept' => 'application/json',
            ],
            RequestOptions::PROGRESS => function ($downloadTotal, $downloadedBytes, $uploadTotal, $uploadedBytes) use (&$bar) {
                if ($uploadTotal > 0) {
                    $bar->setProgress(round($uploadedBytes / $uploadTotal * 100, 2));
                }
            },
        ]);

        $bar->finish();
        $this->newLine();

        $response = json_decode($result->getBody()->getContents());

        if ($encrypt) {
            return sprintf('%s#%s', $response->public_url, base64_encode($key));
        }

        return $response->public_url;
    }

    /**
     * @throws SodiumException
     * @throws Exception
     */
    private function encryptMultipart(array $multipart, string $secret): array
    {
        $multipart[] = ['name' => 'encrypted', 'contents' => 'true'];

        return array_map(function (array $multipart) use ($secret) {
            $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $multipart['contents'] = sodium_crypto_secretbox($multipart['contents'], $nonce, $secret);
            $multipart['contents'] = $nonce . $multipart['contents'];
            return $multipart;
        }, $multipart);
    }
}
