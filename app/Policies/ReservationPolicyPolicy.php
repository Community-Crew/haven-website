<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ReservationPolicy;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ReservationPolicyPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ReservationPolicy');
    }

    public function view(AuthUser $authUser, ReservationPolicy $reservationPolicy): bool
    {
        return $authUser->can('View:ReservationPolicy');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ReservationPolicy');
    }

    public function update(AuthUser $authUser, ReservationPolicy $reservationPolicy): bool
    {
        return $authUser->can('Update:ReservationPolicy');
    }

    public function delete(AuthUser $authUser, ReservationPolicy $reservationPolicy): bool
    {
        return $authUser->can('Delete:ReservationPolicy');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ReservationPolicy');
    }

    public function restore(AuthUser $authUser, ReservationPolicy $reservationPolicy): bool
    {
        return $authUser->can('Restore:ReservationPolicy');
    }

    public function forceDelete(AuthUser $authUser, ReservationPolicy $reservationPolicy): bool
    {
        return $authUser->can('ForceDelete:ReservationPolicy');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ReservationPolicy');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ReservationPolicy');
    }

    public function replicate(AuthUser $authUser, ReservationPolicy $reservationPolicy): bool
    {
        return $authUser->can('Replicate:ReservationPolicy');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ReservationPolicy');
    }
}
