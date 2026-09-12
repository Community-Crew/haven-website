<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\BoardPosition;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class BoardPositionPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BoardPosition');
    }

    public function view(AuthUser $authUser, BoardPosition $boardPosition): bool
    {
        return $authUser->can('View:BoardPosition');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BoardPosition');
    }

    public function update(AuthUser $authUser, BoardPosition $boardPosition): bool
    {
        return $authUser->can('Update:BoardPosition');
    }

    public function delete(AuthUser $authUser, BoardPosition $boardPosition): bool
    {
        return $authUser->can('Delete:BoardPosition');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:BoardPosition');
    }

    public function restore(AuthUser $authUser, BoardPosition $boardPosition): bool
    {
        return $authUser->can('Restore:BoardPosition');
    }

    public function forceDelete(AuthUser $authUser, BoardPosition $boardPosition): bool
    {
        return $authUser->can('ForceDelete:BoardPosition');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BoardPosition');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BoardPosition');
    }

    public function replicate(AuthUser $authUser, BoardPosition $boardPosition): bool
    {
        return $authUser->can('Replicate:BoardPosition');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BoardPosition');
    }
}
