<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\RegistrationCode;
use Illuminate\Auth\Access\HandlesAuthorization;

class RegistrationCodePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:RegistrationCode');
    }

    public function view(AuthUser $authUser, RegistrationCode $registrationCode): bool
    {
        return $authUser->can('View:RegistrationCode');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:RegistrationCode');
    }

    public function update(AuthUser $authUser, RegistrationCode $registrationCode): bool
    {
        return $authUser->can('Update:RegistrationCode');
    }

    public function delete(AuthUser $authUser, RegistrationCode $registrationCode): bool
    {
        return $authUser->can('Delete:RegistrationCode');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:RegistrationCode');
    }

    public function restore(AuthUser $authUser, RegistrationCode $registrationCode): bool
    {
        return $authUser->can('Restore:RegistrationCode');
    }

    public function forceDelete(AuthUser $authUser, RegistrationCode $registrationCode): bool
    {
        return $authUser->can('ForceDelete:RegistrationCode');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:RegistrationCode');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:RegistrationCode');
    }

    public function replicate(AuthUser $authUser, RegistrationCode $registrationCode): bool
    {
        return $authUser->can('Replicate:RegistrationCode');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:RegistrationCode');
    }

}