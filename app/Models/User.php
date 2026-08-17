<?php

namespace App\Models;

use App\Enums\MembershipStatus;
use App\Traits\Auditable;
use Carbon\Traits\Timestamp;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use Auditable, HasFactory, HasRoles, Notifiable, Timestamp;

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

    ];

    protected $casts = [
        'activated_at' => 'datetime',
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
     * Keep Keycloak tokens and the remember token out of the audit trail -
     * they're secrets, not the kind of change history an audit log is for.
     */
    protected function auditExcept(): array
    {
        return [...parent::auditExcept(), 'remember_token', 'keycloak_token', 'keycloak_refresh_token'];
    }
}
