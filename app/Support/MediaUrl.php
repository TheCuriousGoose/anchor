<?php

namespace App\Support;

use Illuminate\Support\Facades\URL;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class MediaUrl
{
    public static function for(Media $media, string $conversion = ''): string
    {
        return $media->disk === 's3'
            ? URL::temporarySignedRoute(
                'media.show',
                now()->addHour(),
                ['media' => $media->getKey(), 'conversion' => $conversion],
            )
            : $media->getUrl($conversion);
    }
}
