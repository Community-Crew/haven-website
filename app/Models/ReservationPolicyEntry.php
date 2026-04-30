<?php

namespace App\Models;

use App\Casts\MinutesToTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationPolicyEntry extends Model
{
    protected $fillable = [
        'reservation_policy_id',
        'day_of_week',
        'start_time',
        'end_time',
    ];

    protected $casts = [
        'start_time' => MinutesToTime::class,
        'end_time' => MinutesToTime::class,
    ];

    public function reservationPolicy(): BelongsTo
    {
        return $this->belongsTo(ReservationPolicy::class);
    }
}
