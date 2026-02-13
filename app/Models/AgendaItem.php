<?php

namespace App\Models;

use App\Traits\HasS3Image;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class AgendaItem extends Model
{
    use HasS3Image, HasSlug, softDeletes;

    public $timestamps = true;

    protected $fillable = [
        'title',
        'description',
        'short_description',
        'image_path',
        'slug',
        'start_date',
        'end_date',
        'user_id',
        'organisation_id',
        'agenda_id',
    ];

    protected $appends = ['image_url'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    public function agenda(): BelongsTo
    {
        return $this->belongsTo(Agenda::class);
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug');
    }
}
