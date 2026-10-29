<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ReservationNote;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ReservationNotePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ReservationNote');
    }

    public function view(AuthUser $authUser, ReservationNote $reservationNote): bool
    {
        return $authUser->can('View:ReservationNote');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ReservationNote');
    }

    public function update(AuthUser $authUser, ReservationNote $reservationNote): bool
    {
        return $authUser->can('Update:ReservationNote');
    }

    public function delete(AuthUser $authUser, ReservationNote $reservationNote): bool
    {
        return $authUser->can('Delete:ReservationNote');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ReservationNote');
    }

    public function restore(AuthUser $authUser, ReservationNote $reservationNote): bool
    {
        return $authUser->can('Restore:ReservationNote');
    }

    public function forceDelete(AuthUser $authUser, ReservationNote $reservationNote): bool
    {
        return $authUser->can('ForceDelete:ReservationNote');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ReservationNote');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ReservationNote');
    }

    public function replicate(AuthUser $authUser, ReservationNote $reservationNote): bool
    {
        return $authUser->can('Replicate:ReservationNote');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ReservationNote');
    }
}
