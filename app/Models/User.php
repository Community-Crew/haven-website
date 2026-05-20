<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
        'keycloak_token',
        'keycloak_refresh_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'remember_token',
        'keycloak_token',
        'keycloak_refresh_token',
    ];

    protected array $runtimeGroups = [];


    /**
     * Get the user's keycloak groups dynamically.
     */
    public function getKeycloakGroupsAttribute(): array
    {
        // Check if API user is authenticated through keycloak-guard, pull straight from the token payload
        if (function_exists('auth') && auth('api')->check()) {
            return auth('api')->token()['groups'] ?? [];
        }

        // If it's a stateless API request but cached manually in runtime.
        if (!empty($this->runtimeGroups)) {
            return $this->runtimeGroups;
        }

        // Fallback for stateful Filament session.
        if (session()->has('keycloak_groups')) {
            return session('keycloak_groups');
        }

        // Fallback decoding from database tokens if available
        $token = session('keycloak_token') ?? $this->keycloak_token;
        if ($token && str_contains($token, '.')) {
            $payload = json_decode(base64_decode(explode('.', $token)[1]), true);
            $groups = $payload['groups'] ?? [];

            if (request()->hasSession()) {
                session(['keycloak_groups' => $groups]);
            }
            return $groups;
        }
        return [];
    }

    public function setKeycloakGroups(array $groups): self
    {
        $this->runtimeGroups = $groups;
        return $this;
    }

    public function hasKeycloakGroup(string $groupPattern): bool
    {
        return in_array($groupPattern, $this->keycloak_groups);
    }

    public function getRolesAttribute(): array
    {
        if (session()->has('roles')) {
            return session('roles');
        }

        $token = session('keycloak_token') ?? $this->keycloak_token;

        if ($token) {
            $payload = json_decode(base64_decode(explode('.', $token)[1]), true);
            $clientName = config('services.keycloak.client_id');
            $roles = $payload['resource_access'][$clientName]['roles'] ?? [];

            session(['roles' => $roles]);

            return $roles;
        }

        return [];
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

    public function canAccessPanel(Panel $panel): bool
    {
        // TODO: Implement canAccessPanel() method.
        return True;
    }
}
