<?php

namespace App\Models;

use App\Traits\Auditable;
use Database\Factories\RegistrationCodeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistrationCode extends Model
{
    /** @use HasFactory<RegistrationCodeFactory> */
    use Auditable, HasFactory;

    protected $fillable = [
        'unit_id',
        'is_used',
        'code',
        'printed_at',
    ];

    protected $casts = [
        'is_used' => 'boolean',
        'printed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (RegistrationCode $registrationCode) {
            // Deliberately not fake()->regexify(...) here: fakerphp/faker is
            // require-dev only, so calling it from application code (rather
            // than a factory/seeder) fatals in production once `composer
            // install --no-dev` has stripped it out.
            $registrationCode->code = sprintf(
                '%s-%s',
                strtoupper(bin2hex(random_bytes(2))),
                strtoupper(bin2hex(random_bytes(2))),
            );
        });
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function togglePrinted(): void
    {
        $this->update(['printed_at' => $this->printed_at ? null : now()]);
    }
}
