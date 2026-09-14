<?php

namespace App\Models;

use App\Observers\BoardPositionAssignmentObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A user actively (or formerly) holding one BoardPosition - decoupled from
 * Membership so a user can hold several simultaneously (e.g. chair of one
 * commission and treasurer of the board at once), which
 * Membership.board_position_id (a single column on the one open membership
 * a user can have) couldn't represent. ended_at is set, not deleted, when
 * revoked - keeps a "who held what, when" history, mirroring
 * BoardPositionSignature.signed_at's mark/unmark pattern. See
 * BoardPositionAssignmentObserver for the role grant/revoke this drives.
 */
#[ObservedBy(BoardPositionAssignmentObserver::class)]
class BoardPositionAssignment extends Model
{
    protected $fillable = [
        'user_id',
        'board_position_id',
        'is_public',
        'sort_order',
        'ended_at',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'sort_order' => 'integer',
        'ended_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function boardPosition(): BelongsTo
    {
        return $this->belongsTo(BoardPosition::class);
    }

    public function isActive(): bool
    {
        return $this->ended_at === null;
    }
}
