<?php

namespace App\Models;

use App\Enums\CheckType;
use App\Enums\CheckVerificationStatus;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * A check-in/check-out is one event with one verification_status, but can
 * carry several photos (e.g. multiple angles of the room) submitted
 * together - all reviewed as a single unit, not per-photo.
 *
 * @property-read array<int, string> $photo_urls
 */
class ReservationCheck extends Model
{
    use Auditable;

    protected $fillable = [
        'reservation_id',
        'type',
        'user_id',
        'occurred_at',
        'verification_status',
        'flag_reason',
        'reviewed_by',
        'reviewed_at',
        'rejection_note',
    ];

    protected $casts = [
        'type' => CheckType::class,
        'verification_status' => CheckVerificationStatus::class,
        'occurred_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public const PHOTO_COLLECTION = 'reservation-check-photo';

    protected $appends = ['photo_urls'];

    /**
     * flag_reason describes exactly what the automated pass checks for -
     * kept out of the API response so residents can't use it to learn how
     * to dodge the heuristic. Admins still see it directly via Filament,
     * which binds to model attributes rather than toArray()/JSON.
     */
    protected $hidden = ['flag_reason'];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function photos(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable')->where('collection', self::PHOTO_COLLECTION);
    }

    /**
     * @return array<int, string>
     */
    public function getPhotoUrlsAttribute(): array
    {
        return $this->photos->pluck('url')->filter()->values()->all();
    }

    public function toArray()
    {
        $attributes = parent::toArray();
        $attributes['type'] = $this->type->jsonSerialize();
        $attributes['verification_status'] = $this->verification_status->jsonSerialize();

        return $attributes;
    }
}
