<?php

namespace App\Services;

use App\Models\User;
use App\Services\Keycloak\KeycloakAdminService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Throwable;

/**
 * Single place for adding/removing a Spatie role from a user, keeping their
 * Keycloak group membership in sync alongside it. Reused by the Users
 * resource (Part C) and, later, commission-position assignment (Part E) -
 * don't duplicate this diff/add/remove logic in either of those.
 *
 * The Keycloak half is always best-effort: the local role change is never
 * rolled back if the Keycloak call fails, matching RoleObserver/UserObserver
 * elsewhere in the app. Failures are logged and surfaced as a Filament
 * notification for whoever's looking at the admin at the time.
 */
class UserRoleSyncService
{
    public function addRole(User $user, Role $role): void
    {
        $user->assignRole($role);

        $this->syncKeycloak($user, $role, add: true);
    }

    public function removeRole(User $user, Role $role): void
    {
        $user->removeRole($role);

        $this->syncKeycloak($user, $role, add: false);
    }

    private function syncKeycloak(User $user, Role $role, bool $add): void
    {
        if (! $user->keycloak_id || ! $role->keycloak_group_id) {
            return;
        }

        try {
            $service = app(KeycloakAdminService::class);

            if ($add) {
                $service->addUserToGroup($user->keycloak_id, $role->keycloak_group_id);
            } else {
                $service->removeUserFromGroup($user->keycloak_id, $role->keycloak_group_id);
            }
        } catch (Throwable $e) {
            Log::warning('Keycloak role sync failed for user', [
                'user_id' => $user->id,
                'role' => $role->name,
                'action' => $add ? 'add' : 'remove',
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->title('Keycloak sync failed')
                ->body("Role \"{$role->name}\" was ".($add ? 'assigned' : 'removed').' locally, but the matching Keycloak group change failed. Check the logs.')
                ->danger()
                ->send();
        }
    }
}
