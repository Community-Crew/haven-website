<?php

namespace App\Models;

use App\Enums\MembershipStatus;
use App\Traits\Auditable;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Contracts\Permission as PermissionContract;
use Spatie\Permission\Contracts\Role as RoleContract;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasLocalePreference
{
    /** @use HasFactory<UserFactory> */
    use Auditable, HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'keycloak_id',
        'unit_id',
        'activated_at',
        'locale',
        'privacy_policy_accepted_at',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'privacy_policy_accepted_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'remember_token',
    ];

    protected array $runtimeGroups = [];

    /**
     * Used by Mail::to($user)->send(...) to pick the mail's language - see
     * the 'locale' migration for why it's a stored column rather than
     * derived from Keycloak/Accept-Language on every send.
     */
    public function preferredLocale(): string
    {
        return $this->locale;
    }

    /**
     * True once the user has accepted a privacy policy no older than the
     * one currently in force - see EnsureUserAcceptedPrivacyPolicy.
     */
    public function hasAcceptedCurrentPrivacyPolicy(): bool
    {
        if (! $this->privacy_policy_accepted_at) {
            return false;
        }

        return $this->privacy_policy_accepted_at->greaterThanOrEqualTo(
            PrivacyPolicy::current()->updated_at
        );
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function organisations(): BelongsToMany
    {
        return $this->belongsToMany(Organisation::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function boardPositionSignatures(): HasMany
    {
        return $this->hasMany(BoardPositionSignature::class);
    }

    /**
     * The membership that's still open (pending/active/suspended), if any -
     * there's at most one at a time, see MembershipForm.
     */
    public function currentMembership(): HasOne
    {
        return $this->hasOne(Membership::class)
            ->whereIn('status', array_map(fn (MembershipStatus $status) => $status->value, MembershipStatus::open()))
            ->latestOfMany();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // TODO: Implement canAccessPanel() method.
        return true;
    }

    /**
     * Overrides Spatie's HasPermissions::hasPermissionViaRole() - the
     * method every permission check ($user->can('Some:Permission')) runs
     * through via PermissionRegistrar::registerPermissions()'s Gate::before
     * hook - to exclude roles that are NDA-gated (they're some
     * BoardPosition's shield_role_id, and that position requires_nda) and
     * not yet satisfied by this user.
     *
     * Deliberately doesn't touch role *membership* - hasRole('Voorzitter')
     * still returns true regardless of NDA status, only checked here,
     * scoped to this one permission's candidate roles. A permission also
     * reachable via another, non-gated role is unaffected.
     */
    protected function hasPermissionViaRole(PermissionContract $permission): bool
    {
        if ($this instanceof RoleContract) {
            return false;
        }

        $usableRoles = $permission->roles->reject(
            fn (Role $role) => $this->isRoleSuppressedByNda($role)
        );

        return $this->hasRole($usableRoles);
    }

    /**
     * Deliberately not cached: this needs to be live (unmarking a
     * signature must affect the very next permission check, even against
     * the same already-loaded User instance - a queue worker/artisan
     * command run, or a Pest test, could easily do exactly that within one
     * process). It's one cheap indexed query either way - Spatie's own
     * permission/role data is already cached separately from this.
     */
    private function isRoleSuppressedByNda(Role $role): bool
    {
        $position = BoardPosition::where('shield_role_id', $role->getKey())
            ->where('requires_nda', true)
            ->first();

        return $position !== null && ! $position->ndaSatisfiedFor($this);
    }

    /**
     * Keep Keycloak tokens and the remember token out of the audit trail -
     * they're secrets, not the kind of change history an audit log is for.
     *
     * Note: can't call parent::auditExcept() here - Auditable is a trait,
     * not a parent class, so `parent::` falls through to Eloquent's magic
     * __call() and throws a BadMethodCallException. Repeat 'updated_at'
     * (the trait's own default) explicitly instead.
     */
    protected function auditExcept(): array
    {
        return ['updated_at', 'remember_token', 'keycloak_token', 'keycloak_refresh_token'];
    }
}
