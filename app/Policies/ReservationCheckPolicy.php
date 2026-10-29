<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ReservationCheck;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ReservationCheckPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ReservationCheck');
    }

    public function view(AuthUser $authUser, ReservationCheck $reservationCheck): bool
    {
        return $authUser->can('View:ReservationCheck');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ReservationCheck');
    }

    public function update(AuthUser $authUser, ReservationCheck $reservationCheck): bool
    {
        return $authUser->can('Update:ReservationCheck');
    }

    public function delete(AuthUser $authUser, ReservationCheck $reservationCheck): bool
    {
        return $authUser->can('Delete:ReservationCheck');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ReservationCheck');
    }

    public function restore(AuthUser $authUser, ReservationCheck $reservationCheck): bool
    {
        return $authUser->can('Restore:ReservationCheck');
    }

    public function forceDelete(AuthUser $authUser, ReservationCheck $reservationCheck): bool
    {
        return $authUser->can('ForceDelete:ReservationCheck');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ReservationCheck');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ReservationCheck');
    }

    public function replicate(AuthUser $authUser, ReservationCheck $reservationCheck): bool
    {
        return $authUser->can('Replicate:ReservationCheck');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ReservationCheck');
    }
}
