<?php

namespace App\Http\Controllers\Api\Media;

use App\Models\Media;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * View a media file
 *
 * Resolve a media file's stable UUID to a freshly-signed URL on its
 * underlying (private) disk and redirect to it. Lets stored content (e.g.
 * rich text) reference media by a permanent URL that never expires, since
 * the real signed URL is only ever generated at request time.
 *
 * @param  Media  $media
 * @return RedirectResponse
 */
class MediaShowController
{
    #[Group('Media')]
    public function __invoke(Media $media): RedirectResponse
    {
        $url = $media->temporaryUrl();

        abort_if($url === null, Response::HTTP_NOT_FOUND);

        return redirect()->away($url);
    }
}
