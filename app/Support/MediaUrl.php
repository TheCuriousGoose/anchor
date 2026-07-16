<?php

namespace App\Support;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class MediaUrl
{
    public static function for(Media $media, string $conversion = ''): string
    {
        return $media->disk === 's3'
            ? $media->getTemporaryUrl(now()->addHour(), $conversion)
            : $media->getUrl($conversion);
    }
}
