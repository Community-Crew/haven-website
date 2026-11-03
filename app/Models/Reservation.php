<?php

namespace App\Models;

use App\Enums\CheckType;
use App\Enums\ReservationStatus;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'challenge_age_seconds',
    ];

    /**
     * Admin-only (see bookingSignals()) - kept out of the API so whoever
     * scripts bookings doesn't learn what's measured.
     */
    protected $hidden = ['challenge_age_seconds'];

    protected $casts = [
        'status' => ReservationStatus::class,
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'challenge_age_seconds' => 'integer',
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

    public function checks(): HasMany
    {
        return $this->hasMany(ReservationCheck::class);
    }

    public function checkIn(): HasOne
    {
        return $this->hasOne(ReservationCheck::class)->where('type', CheckType::CHECK_IN);
    }

    public function checkOut(): HasOne
    {
        return $this->hasOne(ReservationCheck::class)->where('type', CheckType::CHECK_OUT);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ReservationNote::class);
    }

    /**
     * Scoped separately from notes() (rather than filtered at the API
     * resource layer alone) so an internal note can never leak to the
     * resident-facing API even if that mapping code changes later - same
     * defense-in-depth reasoning as ReservationCheck's flag_reason being
     * both $hidden and left out of the resource mapping.
     */
    public function visibleToResidentNotes(): HasMany
    {
        return $this->notes()->where('visible_to_resident', true);
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
     * Default: From today onward only when *neither* bound is given - the
     * frontend's "past" tab deliberately sends only an end date (nothing
     * before it counts as "past"), so forcing a >= today floor in that case
     * made past reservations impossible to ever query. An explicit end
     * without a start now means "no lower bound", not "today".
     */
    public function scopeInDateRange(Builder $query, ?string $startDate, ?string $endDate): Builder
    {
        $start = $startDate
            ? now()->parse($startDate)
            : ($endDate ? null : now()->startOfDay());

        $end = $endDate ? now()->parse($endDate)->endOfDay() : null;

        return $query->where(function ($q) use ($start, $end) {
            if ($start) {
                $q->where('start_at', '>=', $start);
            }

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

    /**
     * Hints that the booking may have been scripted, for admins to weigh -
     * nothing acts on these automatically. Keyed by signal, with a
     * human-readable explanation:
     * - fast: booked within seconds of the proof-of-work challenge being
     *   issued, i.e. of the booking page opening.
     * - window_rush: booked right after the daily reservation window
     *   opened. Normal for an eager resident on its own; telling in
     *   combination with the first.
     *
     * @return array<string, string>
     */
    public function bookingSignals(): array
    {
        $signals = [];

        if ($this->challenge_age_seconds !== null && $this->challenge_age_seconds <= (int) config('reservations.proof_of_work.suspicious_age')) {
            $signals['fast'] = "Booked {$this->challenge_age_seconds}s after opening the booking page";
        }

        if ($this->created_at) {
            $opened = $this->created_at->copy()->setTimeFromTimeString(config('reservations.window_open_time', '20:00'));
            $after = $opened->diffInSeconds($this->created_at, false);

            if ($after >= 0 && $after <= (int) config('reservations.proof_of_work.window_rush')) {
                $signals['window_rush'] = 'Booked '.(int) $after.'s after the reservation window opened';
            }
        }

        return $signals;
    }

    /**
     * Reservations with the `fast` booking signal.
     */
    public function scopeBookedSuspiciouslyFast(Builder $query): Builder
    {
        return $query->whereNotNull('challenge_age_seconds')
            ->where('challenge_age_seconds', '<=', (int) config('reservations.proof_of_work.suspicious_age'));
    }
}
