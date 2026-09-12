<?php

namespace App\Observers;

use App\Services\Keycloak\KeycloakAdminService;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class RoleObserver
{
    public function created(Role $role)
    {
        if ($role->keycloak_group_id) {
            return;
        }

        try {
            $group = app(KeycloakAdminService::class)->findOrCreateGroupPath($role->name);
            $role->keycloak_group_id = $group['id'];
            $role->saveQuietly();
        } catch (\Throwable $e) {
            Log::warning('Keycloak group provisioning failed for role', [
                'role' => $role->name,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function deleting(Role $role)
    {
        if (! $role->keycloak_group_id) {
            return;
        }

        try {
            app(KeycloakAdminService::class)->deleteGroup($role->keycloak_group_id);
        } catch (\Throwable $e) {
            Log::warning('Keycloak group cleaning failed for role', [
                'role' => $role->name,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
