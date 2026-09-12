<?php

namespace App\Models;

use App\Observers\OrganisationObserver;
use App\Traits\Auditable;
use App\Traits\HasS3Image;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

#[ObservedBy(OrganisationObserver::class)]
class Organisation extends Model
{
    use Auditable, HasS3Image, HasSlug;

    protected $fillable = [
        'name',
        'about',
        'slug',
        'image_path',
        'is_commission',
        'nda_text',
    ];

    protected $casts = [
        'is_commission' => 'boolean',
    ];

    protected $appends = ['image_url'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    // Seeded automatically on creation with a default Voorzitter/Lid pair -
    // see OrganisationObserver.
    public function boardPositions(): HasMany
    {
        return $this->hasMany(BoardPosition::class);
    }

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
}
