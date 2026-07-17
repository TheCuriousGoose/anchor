<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class MediaUrl
{
    /**
     * Expiry bucket size in seconds. URLs stay byte-identical within a bucket,
     * so browsers can cache the response instead of re-downloading from S3 on
     * every page render. A URL is valid for at least one bucket, at most two.
     */
    private const WINDOW = 3600;

    public static function for(Media $media, string $conversion = ''): string
    {
        if ($media->disk !== 's3') {
            return $media->getUrl($conversion);
        }

        $expires = Carbon::createFromTimestamp((intdiv(time(), self::WINDOW) + 2) * self::WINDOW);

        return URL::temporarySignedRoute('media.show', $expires, [
            'media' => $media->getKey(),
            'conversion' => $conversion,
            // Cache-buster: a re-generated conversion changes updated_at, which
            // changes the URL, so browsers never serve stale bytes.
            'v' => $media->updated_at?->getTimestamp() ?? 0,
        ]);
    }
}
