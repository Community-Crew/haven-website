<?php

namespace App\Policies;

use App\Models\Organisation;
use App\Models\RegistrationCode;
use App\Models\User;

class OrganisationPolicy
{
    protected string $resource = 'organisations';

    private function getUserRoles(): array
    {
        // Get roles from session and ensure it's an array, default to empty array if null.
        return (array) session('roles', []);
    }
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array('admin-'.$this->resource.'-view', $this->getUserRoles());
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Organisation $organisation): bool
    {
        $roles = $this->getUserRoles();
        return in_array('admin-'.$this->resource.'-view-'.$organisation->id, $roles)
            || $this->viewAny($user); // You can re-use viewAny for the general permission
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return in_array('admin-'.$this->resource.'-create', $this->getUserRoles());
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Organisation $organisation): bool
    {
        $roles = $this->getUserRoles();
        return in_array('admin-'.$this->resource.'-update', $roles)
            || in_array('admin-'.$this->resource.'-update-'.$organisation->id, $roles);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Organisation $organisation): bool
    {
        $roles = $this->getUserRoles();
        return in_array('admin-'.$this->resource.'-delete', $roles)
            || in_array('admin-'.$this->resource.'-delete-'.$organisation->id, $roles);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Organisation $organisation): bool
    {
        $roles = $this->getUserRoles();
        return in_array('admin-'.$this->resource.'-restore', $roles)
            || in_array('admin-'.$this->resource.'-restore-'.$organisation->id, $roles);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Organisation $organisation): bool
    {
        $roles = $this->getUserRoles();
        return in_array('admin-'.$this->resource.'-force_delete', $roles)
            || in_array('admin-'.$this->resource.'-force_delete-'.$organisation->id, $roles);
    }
}
