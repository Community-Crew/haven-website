<?php

namespace App\Policies;

use App\Models\AgendaItem;
use App\Models\User;
use App\Policies\Traits\RoleBasedPermissions;

class AgendaItemPolicy
{
    use RoleBasedPermissions;

    protected string $resource = 'agenda_items';

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
    public function view(User $user, AgendaItem $agendaItem): bool
    {
        return $this->hasRole($user, 'view', $agendaItem->id) || $this->viewAny($user);
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
    public function update(User $user, AgendaItem $agendaItem): bool
    {
        return $this->hasRole($user, 'update', $agendaItem->id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AgendaItem $agendaItem): bool
    {
        return $this->hasRole($user, 'delete', $agendaItem->id);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AgendaItem $agendaItem): bool
    {
        return $this->hasRole($user, 'restore', $agendaItem->id);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AgendaItem $agendaItem): bool
    {
        return $this->hasRole($user, 'force_delete', $agendaItem->id);
    }
}
