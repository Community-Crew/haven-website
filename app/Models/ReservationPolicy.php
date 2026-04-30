<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReservationPolicy extends Model
{
    protected $fillable = [
        'role_name',
        'max_days_in_advance',
    ];

    public function reservationPolicyEntries(): HasMany
    {
        return $this->hasMany(ReservationPolicyEntry::class);
    }

    public function rooms(): BelongsToMany
    {
        return $this->belongsToMany(Room::class);
    }
}
