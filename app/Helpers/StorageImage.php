<?php

namespace App\Helpers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

/**
 * Images of the s3 storage (e.g. avatars and article images) that are delivered by the app.
 */
class StorageImage
{
    /**
     * Redirect to a temporary url of the image in the s3 storage, aborts with 404 without an image. The browser loads the
     * image directly from the storage: creating the url needs no request to the storage, so a slow storage can not block
     * the octane workers. The redirect is cached for half of the lifetime of the url, so a cached url is always valid.
     */
    public static function redirect(?string $path, int $lifetimeInSeconds, bool $isPublic): RedirectResponse
    {
        if (! $path) {
            abort(404);
        }

        $url = Storage::disk('s3')->temporaryUrl($path, now()->addSeconds($lifetimeInSeconds));

        return redirect()->away($url)->header('Cache-Control', ($isPublic ? 'public' : 'private').', max-age='.intdiv($lifetimeInSeconds, 2));
    }
}
