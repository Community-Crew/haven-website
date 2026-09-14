<?php

namespace App\Models;

use App\Observers\BoardPositionObserver;
use App\Services\UserRoleSyncService;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Models\Role;

/**
 * A "board position" - Chair, Secretary, Treasurer for a global (board-wide)
 * position, or a commission-specific one (Garden Commission Coordinator)
 * when organisation_id is set. Drives permissions via shieldRole(), and,
 * for commission-scoped positions, has that role auto-provisioned rather
 * than hand-picked - see BoardPositionObserver (Part E).
 */
#[ObservedBy(BoardPositionObserver::class)]
class BoardPosition extends Model
{
    protected $fillable = [
        'name',
        'organisation_id',
        'shield_role_id',
        'sort_order',
        'requires_nda',
        'nda_text',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'requires_nda' => 'boolean',
    ];

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    public function shieldRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'shield_role_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(BoardPositionAssignment::class);
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(BoardPositionSignature::class);
    }

    /**
     * This position's own NDA text if set, otherwise its commission's
     * default (null for a global position with no organisation, or a
     * commission that hasn't set one).
     */
    public function ndaText(): ?string
    {
        return $this->nda_text ?? $this->organisation?->nda_text;
    }

    /**
     * Whether $user has cleared this position's NDA requirement - always
     * true if the position doesn't require one. Consulted in two places:
     * grantRoleFor() gates the role/Keycloak-group grant itself on it, and
     * User::hasPermissionViaRole()'s override separately gates whether an
     * already-held role's permissions are usable, live, on every permission
     * check - a second layer that still applies if a role is ever assigned
     * some other way (directly via Spatie, outside grantRoleFor()).
     */
    public function ndaSatisfiedFor(User $user): bool
    {
        if (! $this->requires_nda) {
            return true;
        }

        return $this->signatures()
            ->where('user_id', $user->id)
            ->whereNotNull('signed_at')
            ->exists();
    }

    /**
     * Grants this position's role (and, via UserRoleSyncService, its
     * Keycloak group) to $user - but only once ndaSatisfiedFor() is true,
     * so an NDA-gated position's Keycloak group membership doesn't exist
     * until the NDA is actually signed. No-ops otherwise; the caller
     * doesn't need to check first (BoardPositionAssignmentObserver relies
     * on this), and BoardPositionSignatureObserver re-runs this once signed to grant
     * retroactively. User::hasPermissionViaRole()'s NDA check is a second,
     * independent layer on top of this - it still applies even if a role
     * ever ends up assigned some other way.
     */
    public function grantRoleFor(User $user): void
    {
        if (! $this->shieldRole || ! $this->ndaSatisfiedFor($user)) {
            return;
        }

        app(UserRoleSyncService::class)->addRole($user, $this->shieldRole);
    }

    public function revokeRoleFor(User $user): void
    {
        if (! $this->shieldRole) {
            return;
        }

        app(UserRoleSyncService::class)->removeRole($user, $this->shieldRole);
    }
}
