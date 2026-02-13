<?php

namespace App\Policies;

use App\Models\Organisation;
use App\Models\User;
use App\Policies\Traits\RoleBasedPermissions;

class OrganisationPolicy
{
    use RoleBasedPermissions;

    protected string $resource = 'organisations';

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasRole($user, 'view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Organisation $organisation): bool
    {
        return $this->hasRole($user, 'view', $organisation->id) || $this->viewAny($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->hasRole($user, 'create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Organisation $organisation): bool
    {
        return $this->hasRole($user, 'update', $organisation->id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Organisation $organisation): bool
    {
        return $this->hasRole($user, 'delete', $organisation->id);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Organisation $organisation): bool
    {
        return $this->hasRole($user, 'restore', $organisation->id);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Organisation $organisation): bool
    {
        return $this->hasRole($user, 'force_delete', $organisation->id);
    }
}
