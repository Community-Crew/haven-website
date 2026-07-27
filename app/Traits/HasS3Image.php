<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Throwable;

trait HasS3Image
{
    /**
     * Boot the trait and automatically hook into Eloquent lifecycle events.
     */
    protected static function bootHasS3Image(): void
    {
        static::saved(function ($model) {
            $model->clearS3ImageCache();
        });

        static::deleted(function ($model) {
            $model->clearS3ImageCache();
        });
    }

    public function clearS3ImageCache(): void
    {
        if ($this->image_path) {
            $currentKey = "s3_img:{$this->getTable()}:{$this->id}:".md5($this->image_path);
            Cache::forget($currentKey);
        }

        if ($this->isDirty('image_path')) {
            $originalPath = $this->getOriginal('image_path');
            if ($originalPath) {
                $originalKey = "s3_img:{$this->getTable()}:{$this->id}:".md5($originalPath);
                Cache::forget($originalKey);
            }
        }

        $placeholderKey = "s3_img:{$this->getTable()}:{$this->id}:".md5('placeholder.gif');
        Cache::forget($placeholderKey);
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->generateImageUrl()
        );
    }

    protected function generateImageUrl(int $minutes = 15, string $placeholder = 'placeholder.gif'): ?string
    {
        $imagePath = $this->image_path;
        $cacheKey = "s3_img:{$this->getTable()}:{$this->id}:".md5($imagePath ?? $placeholder);
        $cacheSeconds = ($minutes > 2 ? $minutes - 2 : $minutes) * 60;

        return Cache::remember($cacheKey, $cacheSeconds, function () use ($imagePath, $minutes, $placeholder) {
            $disk = Storage::disk('hetzner');
            $key = $imagePath ?: $placeholder;

            try {
                if ($key !== $placeholder && ! $disk->exists($key)) {
                    $key = $placeholder;
                }

                if ($key === $placeholder && ! $disk->exists($key)) {
                    return null;
                }

                return $disk->temporaryUrl($key, Carbon::now()->addMinutes($minutes));
            } catch (Throwable $e) {
                logger()->error('Hetzner S3 Trait Error: '.$e->getMessage());

                return null;
            }
        });
    }
}
