<?php

namespace App\Support;

use Illuminate\Support\Str;

class MediaUrl
{
    public static function resolve(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '/'])) {
            return $path;
        }

        return asset('storage/'.ltrim($path, '/'));
    }
}
