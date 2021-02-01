<?php


namespace App\Services;


use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class Uploader
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'http://maid.sh/api/',
        ]);
    }

    public function multipart(array $multipart, bool $plain): array
    {
        $key = random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
        $multipart = $this->encryptMultipart($multipart, $key);

        if ($plain) {
            $multipart[] = ['name' => 'secret', 'contents' => base64_encode($key)];
        }

        $result = $this->client->request('POST', 'files', [
            RequestOptions::MULTIPART => $multipart,
            RequestOptions::HEADERS => [
                'Accept' => 'application/json',
            ]
        ]);

        $files = json_decode($result->getBody()->getContents(), true);

        return array_map(function ($file) use ($key) {
            if ($file['secret']) {
                return $file['share_url'];
            }

            return sprintf('%s#%s', $file['share_url'], base64_encode($key));
        }, $files);
    }

    private function encryptMultipart(array $multipart, string $secret): array
    {
        return array_map(function (array $multipart) use ($secret) {
            $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $multipart['contents'] = sodium_crypto_secretbox($multipart['contents'], $nonce, $secret);
            file_put_contents('encrypted.bin', $multipart['contents']);
            return $multipart;
        }, $multipart);
    }
}
