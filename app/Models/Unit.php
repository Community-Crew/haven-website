<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    /** @use HasFactory<\Database\Factories\UnitFactory> */
    use HasFactory;

    protected $fillable = [
        'building',
        'floor',
        'unit',
        'subunit',
        'max_residents',
    ];

    protected $appends = ['name'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function registrationCodes(): HasMany
    {
        return $this->hasMany(RegistrationCode::class);
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                switch ($attributes['building']) {
                    case 'Terra':
                        $houseNumber = $attributes['floor'];
                        $unitLetter = $attributes['unit'];
                        $subunit = $attributes['subunit'];

                        return "Terra {$houseNumber} {$unitLetter}-{$subunit}";

                    default:
                        $floor = str_pad($attributes['floor'], 2, '0', STR_PAD_LEFT);
                        $unit = str_pad($attributes['unit'], 2, '0', STR_PAD_LEFT);
                        $subunit = $attributes['subunit'] ?? '';

                        return "{$attributes['building']} {$floor}{$unit}{$subunit}";
                }
            }
        );
    }
}
