<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A dynamic QR code: the printed/posted QR always encodes the same
 * qr.havencommunity.nl/{code} URL, but where that redirects to
 * (destination_url) can be changed later without reprinting anything.
 */
class QrCode extends Model
{
    use Auditable;

    protected $fillable = [
        'name',
        'code',
        'destination_url',
    ];

    protected $casts = [
        'visits' => 'integer',
        'last_visited_at' => 'datetime',
    ];

    protected $appends = ['short_url'];

    protected static function booted(): void
    {
        static::creating(function (QrCode $qrCode) {
            $qrCode->code ??= self::generateUniqueCode();
        });
    }

    protected static function generateUniqueCode(): string
    {
        do {
            // random_bytes-backed, not fake()->... - the latter is
            // require-dev only and fatals in production (see
            // RegistrationCode for the exact bug this avoids repeating).
            $code = Str::lower(Str::random(7));
        } while (self::where('code', $code)->exists());

        return $code;
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    protected function shortUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => rtrim(config('services.qr_app_url'), '/').'/'.$this->code,
        );
    }

    /**
     * Atomic: safe to call concurrently from many simultaneous scans.
     */
    public function recordVisit(): void
    {
        $this->increment('visits', 1, ['last_visited_at' => now()]);
    }
}
