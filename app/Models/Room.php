<?php

namespace App\Models;

use App\RoomStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    /** @use HasFactory<\Database\Factories\RoomFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'location',
        'status',
    ];

    protected $casts = [
        'status' => RoomStatus::class,
    ]
}
