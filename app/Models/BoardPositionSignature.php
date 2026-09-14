<?php

namespace App\Models;

use App\Observers\BoardPositionSignatureObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Records a user having signed the NDA for one specific board position
 * (each position has its own NDA text, per BoardPosition::requires_nda) -
 * see BoardPosition::ndaSatisfiedFor() for the actual gating check, and
 * BoardPositionSignatureObserver for how (un)signing grants/revokes the
 * position's role and Keycloak group membership.
 */
#[ObservedBy(BoardPositionSignatureObserver::class)]
class BoardPositionSignature extends Model
{
    protected $fillable = [
        'user_id',
        'board_position_id',
        'signed_at',
        'external_reference',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function boardPosition(): BelongsTo
    {
        return $this->belongsTo(BoardPosition::class);
    }
}
