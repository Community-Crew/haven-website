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

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
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
     * true if the position doesn't require one. Doesn't gate role
     * *assignment* (see grantRoleFor()) - it gates whether the role's
     * permissions are actually usable, via User::hasPermissionViaRole()'s
     * override, which calls this per-role on every permission check.
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
     * Grants this position's role to $user via UserRoleSyncService.
     * Unconditional beyond having a role configured at all - a user always
     * gets to *hold* the role; whether its permissions actually do anything
     * is a separate, per-check concern (ndaSatisfiedFor(), consulted by
     * User::hasPermissionViaRole()), not something enforced at grant time.
     */
    public function grantRoleFor(User $user): void
    {
        if (! $this->shieldRole) {
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
