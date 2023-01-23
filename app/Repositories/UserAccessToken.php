<?php

namespace App\Repositories;

use App\Exceptions\LoginRequiredException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class UserAccessToken
{
    public function getAccessToken(): ?string
    {
        if (env('MAID_ACCESS_TOKEN')) {
            return env('MAID_ACCESS_TOKEN');
        }

        if (!Storage::exists('credentials.json')) {
            throw LoginRequiredException::fromMissingCredentialsFile();
        }

        $credentials = json_decode(Storage::get('credentials.json'));

        if (Carbon::parse($credentials->expires_at)->isPast()) {
            throw LoginRequiredException::fromExpiredToken();
        }

        return $credentials->access_token;
    }
}
