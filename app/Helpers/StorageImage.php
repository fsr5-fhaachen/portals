<?php

namespace App\Helpers;

use finfo;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

/**
 * Images of the s3 storage (e.g. avatars and article images) that are delivered by the app.
 */
class StorageImage
{
    /**
     * Get the image of the s3 storage as response, aborts with 404 if it does not exist. The image is loaded completely
     * instead of streamed: a streamed s3 response blocks the octane worker until the request times out.
     */
    public static function response(?string $path, string $cacheControl): Response
    {
        $content = $path ? Storage::disk('s3')->get($path) : null;
        if ($content === null) {
            abort(404);
        }

        return response($content, 200, [
            'Content-Type' => (new finfo(FILEINFO_MIME_TYPE))->buffer($content),
            'Cache-Control' => $cacheControl,
        ]);
    }
}
