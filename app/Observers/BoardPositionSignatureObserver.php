<?php

namespace App\Observers;

use App\Enums\MembershipStatus;
use App\Models\BoardPositionSignature;
use App\Models\Membership;

/**
 * BoardPosition::grantRoleFor() already refuses to grant a requires_nda
 * position's role/Keycloak group until ndaSatisfiedFor() is true, which
 * covers a fresh membership assignment. This observer handles the other
 * direction - a signature being (un)marked or deleted after the position
 * was already assigned - by re-running grant/revoke for whichever
 * currently-open membership holds that exact position. A user only ever
 * has one open membership at a time (Membership::hasOpenMembershipFor()'s
 * invariant), so there's at most one to resync.
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

        $this->openMembershipHolding($signature)?->boardPosition?->revokeRoleFor($signature->user);
    }

    private function resync(BoardPositionSignature $signature): void
    {
        $membership = $this->openMembershipHolding($signature);

        if (! $membership) {
            return;
        }

        if ($signature->signed_at) {
            $membership->boardPosition->grantRoleFor($signature->user);
        } else {
            $membership->boardPosition->revokeRoleFor($signature->user);
        }
    }

    private function openMembershipHolding(BoardPositionSignature $signature): ?Membership
    {
        return Membership::query()
            ->where('user_id', $signature->user_id)
            ->where('board_position_id', $signature->board_position_id)
            ->whereIn('status', array_map(fn (MembershipStatus $status) => $status->value, MembershipStatus::open()))
            ->with('boardPosition')
            ->first();
    }
}
