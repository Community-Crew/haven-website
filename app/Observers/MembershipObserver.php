<?php

namespace App\Observers;

use App\Mail\MembershipStatusChangedMail;
use App\Models\BoardPosition;
use App\Models\Membership;
use Illuminate\Support\Facades\Mail;

class MembershipObserver
{
    public function created(Membership $membership): void
    {
        // Nothing was granted before creation, regardless of what
        // attributes the record was created with - so "previous" is
        // unconditionally nothing/closed here, unlike updated()'s diff
        // against actual prior values.
        $this->syncBoardPositionRole($membership, previousPositionId: null, previousWasOpen: false);
    }

    public function updated(Membership $membership): void
    {
        if ($membership->wasChanged('status')) {
            Mail::to($membership->user)->send(new MembershipStatusChangedMail($membership));
        }

        if ($membership->wasChanged(['board_position_id', 'status'])) {
            $this->syncBoardPositionRole(
                $membership,
                previousPositionId: $membership->getOriginal('board_position_id'),
                previousWasOpen: $membership->getOriginal('status')->isOpen(),
            );
        }
    }

    /**
     * A user only ever has one open membership at a time (enforced in
     * MembershipForm::statusComponents()), so deleting one holding a
     * position is a clean revoke with no cross-membership conflict to
     * worry about.
     */
    public function deleting(Membership $membership): void
    {
        if ($membership->board_position_id && $membership->status->isOpen()) {
            $membership->boardPosition?->revokeRoleFor($membership->user);
        }
    }

    /**
     * Diffs the position a membership *effectively* held before this change
     * against what it holds now - "effectively" meaning only while the
     * membership's status counts as open, so a position change and a status
     * change (ending a membership, reactivating one, ...) both fall out of
     * the same comparison instead of needing separate handling.
     */
    private function syncBoardPositionRole(Membership $membership, ?int $previousPositionId, bool $previousWasOpen): void
    {
        $previousEffectiveId = $previousWasOpen ? $previousPositionId : null;
        $currentEffectiveId = $membership->status->isOpen() ? $membership->board_position_id : null;

        if ($previousEffectiveId === $currentEffectiveId) {
            return;
        }

        if ($previousEffectiveId && ($previousPosition = BoardPosition::find($previousEffectiveId))) {
            $previousPosition->revokeRoleFor($membership->user);
        }

        if ($currentEffectiveId && ($currentPosition = BoardPosition::find($currentEffectiveId))) {
            // Best-effort NDA gate: grantRoleFor() no-ops if it isn't
            // satisfied yet. board_position_id is still saved regardless -
            // the assignment is on record even before the role follows.
            $currentPosition->grantRoleFor($membership->user);
        }
    }
}
