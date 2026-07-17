<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    public function show(Request $request, Media $media, string $conversion = ''): Response|StreamedResponse
    {
        $etag = '"'.sha1($media->uuid.'|'.$conversion.'|'.($media->updated_at?->getTimestamp() ?? 0)).'"';

        $headers = [
            'Content-Type' => $media->mime_type,
            // The URL is unique per media version (see MediaUrl), so the cached
            // response can be treated as immutable: repeat views cost zero
            // requests to us and zero to S3 while the URL is current.
            'Cache-Control' => 'private, max-age=86400, immutable',
            'ETag' => $etag,
        ];

        if ($request->headers->get('If-None-Match') === $etag) {
            return response()->noContent(304, $headers);
        }

        $path = $media->getPathRelativeToRoot($conversion);
        $disk = Storage::disk($media->disk);
        $stream = $disk->readStream($path);

        abort_unless(is_resource($stream), 404);

        return response()->stream(function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        }, 200, $headers);
    }
}
