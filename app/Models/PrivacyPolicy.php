<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

/**
 * Single-row model: the privacy policy content lives in exactly one record
 * (id 1, seeded by its creating migration). Edited via the singleton
 * PrivacyPolicy Filament page and served publicly through
 * GET /api/v1/privacy-policy.
 */
class PrivacyPolicy extends Model
{
    use Auditable;

    public $fillable = [
        'content',
        'content_en',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1], ['content' => '']);
    }
}
