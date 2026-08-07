<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Encoders\GifEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\Laravel\Facades\Image;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

class Media extends Model
{
    /**
     * Images are capped to this on their longest edge and re-encoded at
     * reduced quality on upload - no reason to store (and ship on every
     * view) a 4K+ photo for what's usually rendered a fraction of that size.
     */
    public const MAX_DIMENSION = 2000;

    public const QUALITY = 82;

    protected $table = 'media';

    protected $fillable = [
        'uuid',
        'disk',
        'path',
        'collection',
        'name',
        'mime_type',
        'size',
        'mediable_type',
        'mediable_id',
    ];

    protected $appends = ['url'];

    protected static function booted(): void
    {
        static::creating(function (Media $media) {
            $media->uuid ??= (string) Str::uuid();
        });

        static::deleting(function (Media $media) {
            $media->deleteFromDisk();
            $media->clearTemporaryUrlCache();
        });
    }

    /**
     * Resize/compress an uploaded file and store it as a new Media record.
     * Shared by every upload path (rich-text attachments, cover images, ...)
     * so they all get identical treatment.
     *
     * Deliberately reads via $file->get() (a single GetObject) rather than
     * $file->getSize()/getMimeType()/getRealPath() - when the Livewire temp
     * upload disk is S3-backed, those each trigger a HeadObject call that
     * has proven unreliable. Everything else is derived from the encoded
     * result instead, with no further network round-trips.
     */
    public static function createFromUploadedFile(
        TemporaryUploadedFile $file,
        string $collection,
        string $disk = 'hetzner',
        ?int $maxDimension = null,
        ?int $quality = null,
    ): self {
        $image = Image::read($file->get())
            ->scaleDown(width: $maxDimension ?? self::MAX_DIMENSION, height: $maxDimension ?? self::MAX_DIMENSION);

        // Normalize everything to WebP so stored files are consistent and
        // small. The one exception is animated GIFs: converting those to
        // WebP would keep only the first frame, so they're re-encoded as
        // GIF instead to preserve the animation.
        $encoded = $image->isAnimated()
            ? $image->encode(new GifEncoder)
            : $image->encode(new WebpEncoder(quality: $quality ?? self::QUALITY));

        $path = "media/{$collection}/".Str::uuid().'.'.self::extensionForMediaType($encoded->mediaType());

        Storage::disk($disk)->put($path, (string) $encoded);

        return self::create([
            'disk' => $disk,
            'path' => $path,
            'collection' => $collection,
            'name' => $file->getClientOriginalName(),
            'mime_type' => $encoded->mediaType(),
            'size' => $encoded->size(),
        ]);
    }

    protected static function extensionForMediaType(string $mediaType): string
    {
        return match ($mediaType) {
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => 'bin',
        };
    }

    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->temporaryUrl(),
        );
    }

    /**
     * Remove the underlying file from its disk. Safe to call even if it's
     * already gone (e.g. deleted out-of-band).
     */
    public function deleteFromDisk(): void
    {
        try {
            $disk = Storage::disk($this->disk);

            if ($disk->exists($this->path)) {
                $disk->delete($this->path);
            }
        } catch (Throwable $e) {
            logger()->error('Media deleteFromDisk error: '.$e->getMessage());
        }
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }

    public function clearTemporaryUrlCache(): void
    {
        Cache::forget("media_url:{$this->uuid}");
    }

    /**
     * Resolve a short-lived, signed URL for this file. Cached just under the
     * signed URL's own expiry so repeated views don't re-sign on every request.
     */
    public function temporaryUrl(int $minutes = 15): ?string
    {
        $cacheKey = "media_url:{$this->uuid}";
        $cacheSeconds = ($minutes > 2 ? $minutes - 2 : $minutes) * 60;

        return Cache::remember($cacheKey, $cacheSeconds, function () use ($minutes) {
            $disk = Storage::disk($this->disk);

            try {
                if (! $disk->exists($this->path)) {
                    return null;
                }

                return $disk->temporaryUrl($this->path, Carbon::now()->addMinutes($minutes));
            } catch (Throwable $e) {
                logger()->error('Media temporaryUrl error: '.$e->getMessage());

                return null;
            }
        });
    }
}
