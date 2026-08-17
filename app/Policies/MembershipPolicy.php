<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Membership;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class MembershipPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Membership');
    }

    public function view(AuthUser $authUser, Membership $membership): bool
    {
        return $authUser->can('View:Membership');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Membership');
    }

    public function update(AuthUser $authUser, Membership $membership): bool
    {
        return $authUser->can('Update:Membership');
    }

    public function delete(AuthUser $authUser, Membership $membership): bool
    {
        return $authUser->can('Delete:Membership');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Membership');
    }

    public function restore(AuthUser $authUser, Membership $membership): bool
    {
        return $authUser->can('Restore:Membership');
    }

    public function forceDelete(AuthUser $authUser, Membership $membership): bool
    {
        return $authUser->can('ForceDelete:Membership');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Membership');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Membership');
    }

    public function replicate(AuthUser $authUser, Membership $membership): bool
    {
        return $authUser->can('Replicate:Membership');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Membership');
    }
}
