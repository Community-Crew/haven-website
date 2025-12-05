<?php

namespace App\Models;

use App\RoomStatus;
use App\Traits\HasS3Image;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Room extends Model
{
    use HasSlug, HasS3Image;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'location',
        'image_path',
        'status',
    ];

    protected $casts = [
        'status' => RoomStatus::class,
    ];

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    protected static function boot(): void
    {
        parent::boot();

        // Automatically generate slug when creating a Room
        static::creating(function ($room) {
            if (empty($room->slug)) {
                $room->slug = Str::slug($room->name);
            }
        });

        // Optional: Update slug if the name changes
        static::updating(function ($room) {
            if ($room->isDirty('name') && empty($room->getDirty()['slug'] ?? null)) {
                $room->slug = Str::slug($room->name);
            }
        });
    }

}
