<?php

namespace App\Observers;

use App\Models\BoardPositionAssignment;
use App\Models\BoardPositionSignature;

/**
 * BoardPosition::grantRoleFor() already refuses to grant a requires_nda
 * position's role/Keycloak group until ndaSatisfiedFor() is true, which
 * covers a fresh BoardPositionAssignment. This observer handles the other
 * direction - a signature being (un)marked or deleted after the position
 * was already assigned - by re-running grant/revoke for whichever
 * currently-active BoardPositionAssignment holds that exact position. A
 * user can hold a given position at most once at a time (enforced at the
 * BoardPositionAssignmentResource form layer, not a DB constraint), so
 * there's at most one to resync.
 */
class BoardPositionSignatureObserver
{
    public function created(BoardPositionSignature $signature): void
    {
        $this->resync($signature);
    }

    public function updated(BoardPositionSignature $signature): void
    {
        if ($signature->wasChanged('signed_at')) {
            $this->resync($signature);
        }
    }

    /**
     * A deleted signature can no longer justify a grant it made - revoke,
     * same as unmarking. No-op if it was never signed to begin with.
     */
    public function deleting(BoardPositionSignature $signature): void
    {
        if (! $signature->signed_at) {
            return;
        }

        $this->activeAssignmentHolding($signature)?->boardPosition?->revokeRoleFor($signature->user);
    }

    private function resync(BoardPositionSignature $signature): void
    {
        $assignment = $this->activeAssignmentHolding($signature);

        if (! $assignment) {
            return;
        }

        if ($signature->signed_at) {
            $assignment->boardPosition->grantRoleFor($signature->user);
        } else {
            $assignment->boardPosition->revokeRoleFor($signature->user);
        }
    }

    private function activeAssignmentHolding(BoardPositionSignature $signature): ?BoardPositionAssignment
    {
        return BoardPositionAssignment::query()
            ->where('user_id', $signature->user_id)
            ->where('board_position_id', $signature->board_position_id)
            ->whereNull('ended_at')
            ->with('boardPosition')
            ->first();
    }
}
