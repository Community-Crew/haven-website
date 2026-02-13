<?php

namespace App\Policies\Traits;

use App\Models\User;

trait RoleBasedPermissions
{
    protected function hasRole(User $user, string $action, $resourceId = null): bool
    {
        $roles = (array) session('roles', []);
        $resource = $this->resource;

        // Check both general permission and specific resource permission
        return in_array("admin-{$resource}-{$action}", $roles) ||
            ($resourceId && in_array("admin-{$resource}-{$action}-{$resourceId}", $roles));
    }
}
