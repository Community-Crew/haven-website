<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Admin-customizable membership category (Regular, Associate, Honorary, ...).
 * Deliberately a data-backed lookup rather than a PHP enum, since the exact
 * set of types is expected to change without a code deploy.
 */
class MemberType extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'name',
        'description',
        'has_voting_rights',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'has_voting_rights' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }
}
