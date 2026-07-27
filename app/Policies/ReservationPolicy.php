<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Reservation;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ReservationPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Reservation');
    }

    public function view(AuthUser $authUser, Reservation $reservation): bool
    {
        return $authUser->can('View:Reservation');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Reservation');
    }

    public function update(AuthUser $authUser, Reservation $reservation): bool
    {
        if ($authUser->can('Update:Reservation')) {
            return true;
        }

        return (int) $authUser->getAuthIdentifier() === (int) $reservation->user_id;
    }

    public function delete(AuthUser $authUser, Reservation $reservation): bool
    {
        return $authUser->can('Delete:Reservation');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Reservation');
    }

    public function restore(AuthUser $authUser, Reservation $reservation): bool
    {
        return $authUser->can('Restore:Reservation');
    }

    public function forceDelete(AuthUser $authUser, Reservation $reservation): bool
    {
        return $authUser->can('ForceDelete:Reservation');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Reservation');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Reservation');
    }

    public function replicate(AuthUser $authUser, Reservation $reservation): bool
    {
        return $authUser->can('Replicate:Reservation');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Reservation');
    }
}
