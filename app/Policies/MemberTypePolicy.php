<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MemberType;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class MemberTypePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:MemberType');
    }

    public function view(AuthUser $authUser, MemberType $memberType): bool
    {
        return $authUser->can('View:MemberType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:MemberType');
    }

    public function update(AuthUser $authUser, MemberType $memberType): bool
    {
        return $authUser->can('Update:MemberType');
    }

    public function delete(AuthUser $authUser, MemberType $memberType): bool
    {
        return $authUser->can('Delete:MemberType');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:MemberType');
    }

    public function restore(AuthUser $authUser, MemberType $memberType): bool
    {
        return $authUser->can('Restore:MemberType');
    }

    public function forceDelete(AuthUser $authUser, MemberType $memberType): bool
    {
        return $authUser->can('ForceDelete:MemberType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:MemberType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:MemberType');
    }

    public function replicate(AuthUser $authUser, MemberType $memberType): bool
    {
        return $authUser->can('Replicate:MemberType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:MemberType');
    }
}
