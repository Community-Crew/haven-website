<?php

namespace App\Models;

use App\Enums\ReservationStatus;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'name',
        'start_at',
        'end_at',
        'room_id',
        'user_id',
        'share_user',
        'status',
        'organisation_id',
    ];

    protected $casts = [
        'status' => ReservationStatus::class,
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    /**
     * Scope: Filter by status (e.g., pending, confirmed, cancelled)
     */
    public function scopeWithStatus(Builder $query, ?string $status): Builder
    {
        return $query->when($status, function ($q) use ($status) {
            $q->where('status', $status);
        });
    }

    /**
     * Scope: Filter reservations within a date window.
     * Default: From today onward if parameters are missing.
     */
    public function scopeInDateRange(Builder $query, ?string $startDate, ?string $endDate): Builder
    {
        $start = $startDate ? now()->parse($startDate) : now()->startOfDay();

        $end = $endDate ? now()->parse($endDate)->endOfDay() : null;

        return $query->where(function ($q) use ($start, $end) {
            $q->where('start_at', '>=', $start);

            if ($end) {
                $q->where('end_at', '<=', $end);
            }
        });
    }

    /**
     * Scope: Filter reservations by a specific room slug.
     */
    public function scopeForRoomSlug(Builder $query, ?string $slug): Builder
    {
        return $query->when($slug, function ($q) use ($slug) {
            $q->whereHas('room', function ($innerQuery) use ($slug) {
                $innerQuery->where('slug', $slug);
            });
        });
    }

    public function toArray()
    {
        $attributes = parent::toArray();
        $attributes['status'] = $this->status->jsonSerialize();

        return $attributes;
    }
}
