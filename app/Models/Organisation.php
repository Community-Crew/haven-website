<?php

namespace App\Models;

use App\Traits\HasS3Image;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Organisation extends Model
{
    use HasSlug, HasS3Image;

    protected $fillable = [
        'name',
        'about',
        'slug',
        'image_path',
    ];

    protected $appends = ['image_url'];

    public function users(): belongsToMany
    {
        return $this->belongsToMany(User::class);
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
