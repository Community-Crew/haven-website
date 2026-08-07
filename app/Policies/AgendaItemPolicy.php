<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AgendaItem;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class AgendaItemPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:AgendaItem');
    }

    public function view(AuthUser $authUser, AgendaItem $agendaItem): bool
    {
        return $authUser->can('View:AgendaItem');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:AgendaItem');
    }

    public function update(AuthUser $authUser, AgendaItem $agendaItem): bool
    {
        return $authUser->can('Update:AgendaItem');
    }

    public function delete(AuthUser $authUser, AgendaItem $agendaItem): bool
    {
        return $authUser->can('Delete:AgendaItem');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:AgendaItem');
    }

    public function restore(AuthUser $authUser, AgendaItem $agendaItem): bool
    {
        return $authUser->can('Restore:AgendaItem');
    }

    public function forceDelete(AuthUser $authUser, AgendaItem $agendaItem): bool
    {
        return $authUser->can('ForceDelete:AgendaItem');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:AgendaItem');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:AgendaItem');
    }

    public function replicate(AuthUser $authUser, AgendaItem $agendaItem): bool
    {
        return $authUser->can('Replicate:AgendaItem');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:AgendaItem');
    }
}
