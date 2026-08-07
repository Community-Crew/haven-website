<?php

namespace App\Models;

use App\Enums\RoomStatus;
use App\Traits\Auditable;
use App\Traits\HasCoverMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * @property-read string|null $image_url
 */
class Room extends Model
{
    use Auditable, HasCoverMedia, HasSlug;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'location',
        'status',
        'image_path',
    ];

    protected $casts = [
        'status' => RoomStatus::class,
    ];

    /**
     * Media collection name for this model's cover image - see HasCoverMedia
     * and MediaCoverPicker.
     */
    protected string $coverMediaCollection = 'rooms-cover';

    protected $appends = ['image_url'];

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

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function reservationPolicies(): BelongsToMany
    {
        return $this->belongsToMany(ReservationPolicy::class);
    }

    public function toArray()
    {
        $attributes = parent::toArray();

        $attributes['status'] = $this->status->jsonSerialize();

        return $attributes;
    }
}
