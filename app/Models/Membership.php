<?php

namespace App\Models;

use App\Enums\MembershipStatus;
use App\Observers\MembershipObserver;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A user's membership of the association for a given stretch of time. A
 * user can hold several of these over time (rejoining, changing type), but
 * only one non-ended (pending/active/suspended) at once - enforced in the
 * Filament form, see MembershipForm.
 */
#[ObservedBy(MembershipObserver::class)]
class Membership extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'user_id',
        'member_type_id',
        'status',
        'joined_at',
        'ended_at',
        'has_voting_rights',
        'board_role',
        'notes',
    ];

    protected $casts = [
        'status' => MembershipStatus::class,
        'joined_at' => 'date',
        'ended_at' => 'date',
        'has_voting_rights' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function memberType(): BelongsTo
    {
        return $this->belongsTo(MemberType::class);
    }

    /**
     * Whether the given user already has an open (pending/active/suspended)
     * membership, other than $exceptId. Shared by MembershipForm and
     * MembershipsRelationManager to enforce "one at a time" without
     * duplicating the query.
     */
    public static function hasOpenMembershipFor(int $userId, ?int $exceptId = null): bool
    {
        return static::query()
            ->where('user_id', $userId)
            ->whereIn('status', array_map(fn (MembershipStatus $status) => $status->value, MembershipStatus::open()))
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->exists();
    }
}
