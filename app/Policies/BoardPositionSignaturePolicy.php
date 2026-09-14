<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\BoardPositionSignature;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class BoardPositionSignaturePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BoardPositionSignature');
    }

    public function view(AuthUser $authUser, BoardPositionSignature $boardPositionSignature): bool
    {
        return $authUser->can('View:BoardPositionSignature');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BoardPositionSignature');
    }

    public function update(AuthUser $authUser, BoardPositionSignature $boardPositionSignature): bool
    {
        return $authUser->can('Update:BoardPositionSignature');
    }

    public function delete(AuthUser $authUser, BoardPositionSignature $boardPositionSignature): bool
    {
        return $authUser->can('Delete:BoardPositionSignature');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:BoardPositionSignature');
    }

    public function restore(AuthUser $authUser, BoardPositionSignature $boardPositionSignature): bool
    {
        return $authUser->can('Restore:BoardPositionSignature');
    }

    public function forceDelete(AuthUser $authUser, BoardPositionSignature $boardPositionSignature): bool
    {
        return $authUser->can('ForceDelete:BoardPositionSignature');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BoardPositionSignature');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BoardPositionSignature');
    }

    public function replicate(AuthUser $authUser, BoardPositionSignature $boardPositionSignature): bool
    {
        return $authUser->can('Replicate:BoardPositionSignature');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BoardPositionSignature');
    }
}
