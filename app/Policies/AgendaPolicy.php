<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Agenda;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class AgendaPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Agenda');
    }

    public function view(AuthUser $authUser, Agenda $agenda): bool
    {
        return $authUser->can('View:Agenda');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Agenda');
    }

    public function update(AuthUser $authUser, Agenda $agenda): bool
    {
        return $authUser->can('Update:Agenda');
    }

    public function delete(AuthUser $authUser, Agenda $agenda): bool
    {
        return $authUser->can('Delete:Agenda');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Agenda');
    }

    public function restore(AuthUser $authUser, Agenda $agenda): bool
    {
        return $authUser->can('Restore:Agenda');
    }

    public function forceDelete(AuthUser $authUser, Agenda $agenda): bool
    {
        return $authUser->can('ForceDelete:Agenda');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Agenda');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Agenda');
    }

    public function replicate(AuthUser $authUser, Agenda $agenda): bool
    {
        return $authUser->can('Replicate:Agenda');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Agenda');
    }
}
