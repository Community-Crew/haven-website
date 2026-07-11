<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Organisation;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrganisationPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Organisation');
    }

    public function view(AuthUser $authUser, Organisation $organisation): bool
    {
        return $authUser->can('View:Organisation');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Organisation');
    }

    public function update(AuthUser $authUser, Organisation $organisation): bool
    {
        return $authUser->can('Update:Organisation');
    }

    public function delete(AuthUser $authUser, Organisation $organisation): bool
    {
        return $authUser->can('Delete:Organisation');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Organisation');
    }

    public function restore(AuthUser $authUser, Organisation $organisation): bool
    {
        return $authUser->can('Restore:Organisation');
    }

    public function forceDelete(AuthUser $authUser, Organisation $organisation): bool
    {
        return $authUser->can('ForceDelete:Organisation');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Organisation');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Organisation');
    }

    public function replicate(AuthUser $authUser, Organisation $organisation): bool
    {
        return $authUser->can('Replicate:Organisation');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Organisation');
    }

}