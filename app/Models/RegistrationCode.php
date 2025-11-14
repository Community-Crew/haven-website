<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistrationCode extends Model
{
    /** @use HasFactory<\Database\Factories\RegistrationCodeFactory> */
    use HasFactory;

    protected $fillable =[
        'unit_id',
        'is_used',
    ];

    protected static function booted(): void
    {
        static::creating(function (RegistrationCode $registrationCode) {
            $registrationCode->code = fake()->regexify('[0-9A-F]{4}-[0-9A-F]{4}');
        });
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
