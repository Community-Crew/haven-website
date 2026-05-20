<?php

namespace App\Policies;

use App\Models\Unit;
use App\Models\User;

class UserPolicy
{
    protected string $resource = 'users';

    private function getUserRoles(): array
    {
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
    public function view(User $user, User $userToView): bool
    {
        $roles = $this->getUserRoles();

        return in_array('admin-'.$this->resource.'-view-'.$userToView->id, $roles)
            || $this->viewAny($user);
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
    public function update(User $user, User $userToView): bool
    {
        $roles = $this->getUserRoles();

        return in_array('admin-'.$this->resource.'-update', $roles)
            || in_array('admin-'.$this->resource.'-update-'.$userToView->id, $roles);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $userToView): bool
    {
        $roles = $this->getUserRoles();

        return in_array('admin-'.$this->resource.'-delete', $roles)
            || in_array('admin-'.$this->resource.'-delete-'.$userToView->id, $roles);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $userToView): bool
    {
        $roles = $this->getUserRoles();

        return in_array('admin-'.$this->resource.'-restore', $roles)
            || in_array('admin-'.$this->resource.'-restore-'.$userToView->id, $roles);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $userToView): bool
    {
        $roles = $this->getUserRoles();

        return in_array('admin-'.$this->resource.'-force_delete', $roles)
            || in_array('admin-'.$this->resource.'-force_delete-'.$userToView->id, $roles);
    }
}
