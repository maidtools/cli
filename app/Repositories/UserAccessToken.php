<?php

namespace App\Repositories;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class UserAccessToken
{
    public function getAccessToken(): ?string
    {
        if (!Storage::exists('credentials.json')) {
            return null;
        }

        $credentials = json_decode(Storage::get('credentials.json'));

        if (Carbon::parse($credentials->expires_at)->isPast()) {
            return null;
        }

        return $credentials->access_token;
    }
}