<?php

namespace App\Models;

use App\Http\Enums\ReservationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reservation extends Model
{
    use HasFactory;

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

    public function clashes(): HasMany
    {
        if (is_null($this->start_at) || is_null($this->end_at)) {
            return $this->hasMany(Reservation::class, 'room_id', 'room_id')->whereRaw('1 = 0');
        }

        return $this->hasMany(Reservation::class, 'room_id', 'room_id')
            ->where('id', '!=', $this->id)
            ->where(function ($query) {
                $query->where('start_at', '<', $this->end_at)
                    ->where('end_at', '>', $this->start_at);
            });
    }

    public function toArray()
    {
        $attributes = parent::toArray();
        $attributes['status'] = $this->status->jsonSerialize();

        return $attributes;
    }
}
