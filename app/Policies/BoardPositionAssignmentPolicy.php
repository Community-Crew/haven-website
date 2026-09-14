<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\BoardPositionAssignment;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class BoardPositionAssignmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BoardPositionAssignment');
    }

    public function view(AuthUser $authUser, BoardPositionAssignment $boardPositionAssignment): bool
    {
        return $authUser->can('View:BoardPositionAssignment');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BoardPositionAssignment');
    }

    public function update(AuthUser $authUser, BoardPositionAssignment $boardPositionAssignment): bool
    {
        return $authUser->can('Update:BoardPositionAssignment');
    }

    public function delete(AuthUser $authUser, BoardPositionAssignment $boardPositionAssignment): bool
    {
        return $authUser->can('Delete:BoardPositionAssignment');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:BoardPositionAssignment');
    }

    public function restore(AuthUser $authUser, BoardPositionAssignment $boardPositionAssignment): bool
    {
        return $authUser->can('Restore:BoardPositionAssignment');
    }

    public function forceDelete(AuthUser $authUser, BoardPositionAssignment $boardPositionAssignment): bool
    {
        return $authUser->can('ForceDelete:BoardPositionAssignment');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BoardPositionAssignment');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BoardPositionAssignment');
    }

    public function replicate(AuthUser $authUser, BoardPositionAssignment $boardPositionAssignment): bool
    {
        return $authUser->can('Replicate:BoardPositionAssignment');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BoardPositionAssignment');
    }
}
