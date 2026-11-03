<?php

namespace App\Services;

use App\Models\User;
use App\Services\Keycloak\KeycloakAdminService;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;
use RuntimeException;
use Spatie\Permission\Models\Role;

/**
 * Vestide staff accounts, managed by Vestide's own managers from their
 * panel. Keycloak is the source of truth: staff are members of the
 * "Vestide" group, managers of its "Vestide/Manager" subgroup (roles
 * "Vestide" and "Vestide-Manager" in Haven, synced on every login - see
 * KeycloakAdminController). Someone who hasn't logged in yet only exists in
 * Keycloak, so the list is read from there rather than from users.
 *
 * Deliberately can't hand Vestide access to an existing account: an email
 * that's already registered (e.g. a resident's) is refused rather than
 * added to the group. That stays a job for Haven's own admins.
 */
class VestideAccountService
{
    public const STAFF_GROUP = 'Vestide';

    public const MANAGER_GROUP = 'Vestide/Manager';

    public function __construct(protected KeycloakAdminService $keycloak) {}

    /**
     * @return Collection<int, array{id: string, name: string, email: string, manager: bool, invited: bool, enabled: bool}>
     */
    public function accounts(): Collection
    {
        $map = fn (array $member, bool $manager) => [
            'id' => $member['id'],
            'name' => trim(($member['firstName'] ?? '').' '.($member['lastName'] ?? '')) ?: ($member['username'] ?? ''),
            'email' => $member['email'] ?? '',
            'manager' => $manager,
            // Still has to follow the invitation link (set a password, verify).
            'invited' => ! empty($member['requiredActions']),
            'enabled' => (bool) ($member['enabled'] ?? true),
        ];

        $managers = collect($this->keycloak->groupMembers($this->role(self::MANAGER_GROUP)->keycloak_group_id))
            ->map(fn (array $member) => $map($member, true));

        return collect($this->keycloak->groupMembers($this->role(self::STAFF_GROUP)->keycloak_group_id))
            ->map(fn (array $member) => $map($member, false))
            ->reject(fn (array $account) => $managers->contains('id', $account['id']))
            ->concat($managers)
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /**
     * @throws AccountAlreadyExists
     */
    public function create(string $email, string $firstName, string $lastName, bool $manager): string
    {
        if ($this->keycloak->findUserByEmail($email)) {
            throw new AccountAlreadyExists($email);
        }

        $id = $this->keycloak->createUser($email, $firstName, $lastName);
        $this->keycloak->addUserToGroup($id, $this->role($manager ? self::MANAGER_GROUP : self::STAFF_GROUP)->keycloak_group_id);
        $this->invite($id);

        return $id;
    }

    public function invite(string $keycloakUserId): void
    {
        $this->keycloak->sendActionsEmail(
            $keycloakUserId,
            ['UPDATE_PASSWORD', 'VERIFY_EMAIL'],
            Filament::getPanel('vestide')->getUrl(),
        );
    }

    public function setManager(string $keycloakUserId, bool $manager): void
    {
        $add = $this->role($manager ? self::MANAGER_GROUP : self::STAFF_GROUP);
        $remove = $this->role($manager ? self::STAFF_GROUP : self::MANAGER_GROUP);

        $this->keycloak->addUserToGroup($keycloakUserId, $add->keycloak_group_id);
        $this->keycloak->removeUserFromGroup($keycloakUserId, $remove->keycloak_group_id);

        // Don't wait for their next login to apply it here.
        if ($user = $this->localUser($keycloakUserId)) {
            $user->removeRole($remove);
            $user->assignRole($add);
        }
    }

    /**
     * Out of both groups, signed out of Keycloak, and the Haven roles gone
     * straight away - otherwise an open Haven session would keep working
     * until it expired.
     */
    public function revoke(string $keycloakUserId): void
    {
        $roles = [$this->role(self::STAFF_GROUP), $this->role(self::MANAGER_GROUP)];

        foreach ($roles as $role) {
            $this->keycloak->removeUserFromGroup($keycloakUserId, $role->keycloak_group_id);
        }

        $this->keycloak->logoutUser($keycloakUserId);

        if ($user = $this->localUser($keycloakUserId)) {
            foreach ($roles as $role) {
                $user->removeRole($role);
            }
        }
    }

    /**
     * The Haven role for a Keycloak group path, created (with its group id
     * filled in) if it doesn't exist yet. Created quietly: RoleObserver
     * would otherwise provision a group named after the flat role name
     * ("Vestide-Manager") instead of using the nested path.
     */
    public function role(string $groupPath): Role
    {
        $name = str_replace('/', '-', $groupPath);
        $role = Role::query()->where('name', $name)->where('guard_name', 'web')->first()
            ?? Role::withoutEvents(fn () => Role::create(['name' => $name, 'guard_name' => 'web']));

        if (! $role->keycloak_group_id) {
            $role->keycloak_group_id = $this->keycloak->findOrCreateGroupPath($groupPath)['id']
                ?? throw new RuntimeException("Keycloak group {$groupPath} could not be resolved");
            $role->saveQuietly();
        }

        return $role;
    }

    protected function localUser(string $keycloakUserId): ?User
    {
        return User::query()->where('keycloak_id', $keycloakUserId)->first();
    }
}
