<?php

namespace App;

use Exception;
use Illuminate\Support\Arr;
use Symfony\Component\Yaml\Yaml;

class Maid
{
    public static function getAccessToken(): ?string
    {
        return 'access_token';
    }

    /**
     * @throws Exception
     */
    public static function getManifest(string|int|null $key = null): mixed
    {
        $filename = getcwd() . DIRECTORY_SEPARATOR . 'maid.yml';

        if (!file_exists($filename)) {
            throw new Exception(sprintf('Maid manifest file not found at %s', $filename));
        }

        return Arr::get(Yaml::parseFile($filename), $key);
    }

    public function withToken(?string $accessToken): self
    {
        return $this;
    }

    public function rollback(string $application)
    {
        dump('Rolling back ' . $application);
    }

    public function deploy(array $attributes)
    {
        dump('Deploying ' . $attributes['manifest']['name']);
    }
}
