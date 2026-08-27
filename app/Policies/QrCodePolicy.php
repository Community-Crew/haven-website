<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\QrCode;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class QrCodePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:QrCode');
    }

    public function view(AuthUser $authUser, QrCode $qrCode): bool
    {
        return $authUser->can('View:QrCode');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:QrCode');
    }

    public function update(AuthUser $authUser, QrCode $qrCode): bool
    {
        return $authUser->can('Update:QrCode');
    }

    public function delete(AuthUser $authUser, QrCode $qrCode): bool
    {
        return $authUser->can('Delete:QrCode');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:QrCode');
    }

    public function restore(AuthUser $authUser, QrCode $qrCode): bool
    {
        return $authUser->can('Restore:QrCode');
    }

    public function forceDelete(AuthUser $authUser, QrCode $qrCode): bool
    {
        return $authUser->can('ForceDelete:QrCode');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:QrCode');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:QrCode');
    }

    public function replicate(AuthUser $authUser, QrCode $qrCode): bool
    {
        return $authUser->can('Replicate:QrCode');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:QrCode');
    }
}
