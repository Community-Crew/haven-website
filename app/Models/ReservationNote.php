<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationNote extends Model
{
    use Auditable;

    protected $fillable = [
        'reservation_id',
        'user_id',
        'body',
        'visible_to_resident',
    ];

    protected $casts = [
        'visible_to_resident' => 'boolean',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
