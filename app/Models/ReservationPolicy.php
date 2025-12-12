<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;

class ReservationPolicy extends Model
{
    protected $fillable = [
        'role_name',
        'max_days_in_advance',
        'weekly_schedule',
    ];

    protected $casts = [
        'weekly_schedule' => AsArrayObject::class,
    ];
}
