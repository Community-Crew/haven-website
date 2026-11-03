<?php

namespace App\Models;

use App\Enums\MembershipStatus;
use App\Services\ResidentOffboardingService;
use App\Traits\Auditable;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
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
    /**
     * Locales the app and mails are translated into - see lang/.
     */
    public const LOCALES = ['nl', 'en'];

    /**
     * 'system' follows the device's prefers-color-scheme.
     */
    public const THEMES = ['system', 'light', 'dark'];

    /**
     * Mirror the column defaults, so a freshly created user (not reloaded
     * from the database) has them too.
     */
    protected $attributes = [
        'locale' => 'nl',
        'theme' => 'system',
    ];

    protected $fillable = [
        'name',
        'email',
        'keycloak_id',
        'unit_id',
        'activated_at',
        'locale',
        'theme',
        'privacy_policy_accepted_at',
        'moved_out_at',
        'anonymized_at',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'privacy_policy_accepted_at' => 'datetime',
        'moved_out_at' => 'datetime',
        'anonymized_at' => 'datetime',
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

    /**
     * Moved out (waiting out the retention period) or already anonymized -
     * either way, no longer someone the app should be in touch with, apart
     * from the move-out notice itself. See ResidentOffboardingService.
     */
    public function isFormerResident(): bool
    {
        return $this->moved_out_at !== null || $this->anonymized_at !== null;
    }

    /**
     * When app:anonymize-former-residents will wipe this user's personal
     * data, or null if they haven't moved out.
     */
    public function anonymizesAt(): ?Carbon
    {
        return $this->moved_out_at?->copy()->addDays(ResidentOffboardingService::RETENTION_DAYS);
    }

    public function scopeCurrentResidents(Builder $query): Builder
    {
        return $query->whereNotNull('activated_at')->whereNull('moved_out_at')->whereNull('anonymized_at');
    }

    public function scopeFormerResidents(Builder $query): Builder
    {
        return $query->where(fn (Builder $query) => $query->whereNotNull('moved_out_at')->orWhereNotNull('anonymized_at'));
    }

    /**
     * Candidates for the Users list's "Possibly moved out" review tab, not
     * yet marked as moved out:
     * - deactivated but with reservation history (so they *were* a resident
     *   - e.g. cleared by a Vestide import before moved_out_at existed), or
     * - still activated, but no login or reservation in $months months.
     */
    public function scopePossiblyMovedOut(Builder $query, int $months = 6): Builder
    {
        $since = now()->subMonths($months);

        return $query
            ->whereNull('moved_out_at')
            ->whereNull('anonymized_at')
            ->where(fn (Builder $query) => $query
                ->where(fn (Builder $query) => $query->whereNull('activated_at')->whereHas('reservations'))
                ->orWhere(fn (Builder $query) => $query
                    ->whereNotNull('activated_at')
                    ->where('activated_at', '<', $since)
                    ->whereDoesntHave('reservations', fn (Builder $query) => $query->where('start_at', '>=', $since))
                    ->whereNotExists(fn ($query) => $query
                        ->from('activity_log')
                        ->where('log_name', 'auth')
                        ->where('event', 'login')
                        ->where('causer_type', (new static)->getMorphClass())
                        ->whereColumn('causer_id', 'users.id')
                        ->where('created_at', '>=', $since))));
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

    /**
     * Check-ins/outs on this user's reservations - including auto-rejected
     * missed check-outs, which nobody submitted.
     */
    public function reservationChecks(): HasManyThrough
    {
        return $this->hasManyThrough(ReservationCheck::class, Reservation::class);
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

    public function boardPositionAssignments(): HasMany
    {
        return $this->hasMany(BoardPositionAssignment::class);
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

    /**
     * Roles (synced from the Keycloak groups "Vestide" and "Vestide/Manager",
     * see VestideAccountService) for the landlord's staff - they grant the
     * Vestide panel only. Managers can also manage the staff accounts.
     */
    public const VESTIDE_ROLE = 'Vestide';

    public const VESTIDE_MANAGER_ROLE = 'Vestide-Manager';

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'vestide') {
            return $this->hasRole([self::VESTIDE_ROLE, self::VESTIDE_MANAGER_ROLE]) || $this->isSuperAdmin();
        }

        // Vestide staff stay in their own panel. Everyone else keeps the
        // existing behaviour: in, with Shield permissions deciding what
        // they actually see.
        // TODO: Implement canAccessPanel() method for the admin panel.
        return ! $this->isVestideOnly();
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(config('filament-shield.super_admin.name'));
    }

    public function isVestideManager(): bool
    {
        return $this->hasRole(self::VESTIDE_MANAGER_ROLE) || $this->isSuperAdmin();
    }

    public function isVestideOnly(): bool
    {
        return $this->roles->isNotEmpty()
            && $this->roles->every(fn (Role $role) => in_array($role->name, [self::VESTIDE_ROLE, self::VESTIDE_MANAGER_ROLE], true));
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
