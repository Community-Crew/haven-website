<?php

namespace App\Observers;

use App\Models\BoardPositionAssignment;

/**
 * Grants/revokes a BoardPositionAssignment's role (subject to
 * BoardPosition::grantRoleFor()'s own NDA gate) as it's created, ended or
 * reinstated (ended_at set/cleared), or deleted while still active.
 */
class BoardPositionAssignmentObserver
{
    public function created(BoardPositionAssignment $assignment): void
    {
        if ($assignment->isActive()) {
            $assignment->boardPosition->grantRoleFor($assignment->user);
        }
    }

    public function updated(BoardPositionAssignment $assignment): void
    {
        if (! $assignment->wasChanged('ended_at')) {
            return;
        }

        if ($assignment->isActive()) {
            $assignment->boardPosition->grantRoleFor($assignment->user);
        } else {
            $assignment->boardPosition->revokeRoleFor($assignment->user);
        }
    }

    /**
     * Deleting an active assignment outright (rather than ending it) still
     * has to revoke - there's nothing left on record to justify the grant.
     */
    public function deleting(BoardPositionAssignment $assignment): void
    {
        if ($assignment->isActive()) {
            $assignment->boardPosition->revokeRoleFor($assignment->user);
        }
    }
}
