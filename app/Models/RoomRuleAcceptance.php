<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One resident accepting a room's rules for one reservation, at one moment.
 * Holds a copy of the accepted text - see the creating migration.
 */
class RoomRuleAcceptance extends Model
{
    public const MOMENT_BOOKING = 'booking';

    public const MOMENT_CHECK_IN = 'check_in';

    protected $fillable = [
        'reservation_id',
        'user_id',
        'room_id',
        'moment',
        'rules_hash',
        'default_rules',
        'rules',
        'accepted_at',
    ];

    protected $casts = [
        'default_rules' => 'array',
        'rules' => 'array',
        'accepted_at' => 'datetime',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
