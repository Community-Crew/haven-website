<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
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
        'is_beneficiary',
        'is_honorary',
        'sort_order',
    ];

    protected $casts = [
        'has_voting_rights' => 'boolean',
        'is_active' => 'boolean',
        'is_beneficiary' => 'boolean',
        'is_honorary' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Beneficiaries and honorary members stay after moving out, so their
     * data isn't anonymized while the membership is open.
     */
    public function scopeKeepsDataAfterMoveOut(Builder $query): Builder
    {
        return $query->where(fn (Builder $type) => $type->where('is_beneficiary', true)->orWhere('is_honorary', true));
    }

    public function keepsDataAfterMoveOut(): bool
    {
        return $this->is_beneficiary || $this->is_honorary;
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }
}
