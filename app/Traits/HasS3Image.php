<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Throwable;

trait HasS3Image
{
    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->generateImageUrl()
        );
    }

    protected function generateImageUrl(int $minutes = 15, string $placeholder = 'placeholder.gif'): ?string
    {
        {
            $disk = Storage::disk('hetzner');

            $key = $this->image_path ?: $placeholder;

            try {
                if (! $disk->exists($key) && $key !== $placeholder) {
                    $key = $placeholder;
                }

                if (! $disk->exists($key)) {
                    return null;
                }

                return $disk->temporaryUrl($key, Carbon::now()->addMinutes($minutes));
            } catch (Throwable $e) {
                return null;
            }
        }
    }

}
