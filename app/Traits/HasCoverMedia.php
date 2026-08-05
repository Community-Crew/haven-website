<?php

namespace App\Traits;

use App\Models\Media;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * A model's "cover image" is just a Media row whose mediable_* points back
 * at it, disambiguated from any other Media a model might accumulate (e.g.
 * rich-text attachments) by collection name. There's no FK column for it -
 * MediaCoverPicker reads/writes this relationship directly via the model's
 * morph class + key, the same way HasMediaAttachments already does for
 * rich-text columns.
 */
trait HasCoverMedia
{
    public function coverMedia(?string $collection = null): ?Media
    {
        return Media::query()
            ->where('mediable_type', $this->getMorphClass())
            ->where('mediable_id', $this->getKey())
            ->where('collection', $collection ?? $this->getCoverMediaCollection())
            ->first();
    }

    public function getCoverMediaCollection(): string
    {
        return $this->coverMediaCollection ?? $this->getTable().'-cover';
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->coverMedia()?->url,
        );
    }
}
