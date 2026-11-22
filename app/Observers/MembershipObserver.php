<?php

namespace App\Observers;

use App\Enums\MembershipStatus;
use App\Mail\MembershipStatusChangedMail;
use App\Mail\ResidentMovedOutMail;
use App\Models\BoardPositionAssignment;
use App\Models\Membership;
use Illuminate\Support\Facades\Mail;

class MembershipObserver
{
    public function updated(Membership $membership): void
    {
        if ($membership->wasChanged('status')) {
            Mail::to($membership->user)->send(new MembershipStatusChangedMail($membership));
        }

        if ($membership->wasChanged('status')
            && $membership->getOriginal('status')->isOpen()
            && ! $membership->status->isOpen()) {
            $this->endActiveBoardPositions($membership);
            $this->restartRetentionAfterKeptMembership($membership);
        }
    }

    /**
     * A beneficiary or honorary member may have moved out years ago. Without
     * this they'd be anonymized on the next daily run, with no notice; now
     * they get the usual retention period and move-out mail. A rejected
     * application never was a membership, so its clock simply keeps running.
     */
    private function restartRetentionAfterKeptMembership(Membership $membership): void
    {
        $user = $membership->user;

        if (! $user->moved_out_at
            || ! $membership->memberType?->keepsDataAfterMoveOut()
            || $membership->getOriginal('status') === MembershipStatus::PENDING) {
            return;
        }

        $user->update(['moved_out_at' => now()]);

        Mail::to($user)->send(new ResidentMovedOutMail($user));
    }

    /**
     * A user only ever has one open membership at a time (enforced in
     * MembershipForm::statusComponents()), so a membership no longer being
     * open means the user themselves is no longer an active member - end
     * every board position they're currently holding, regardless of which
     * commission/position it's for. Board positions themselves can be held
     * several at once (see BoardPositionAssignment) - this ends all of
     * them together, there's no per-position distinction to make here.
     */
    public function deleting(Membership $membership): void
    {
        if ($membership->status->isOpen()) {
            $this->endActiveBoardPositions($membership);
        }
    }

    private function endActiveBoardPositions(Membership $membership): void
    {
        BoardPositionAssignment::query()
            ->where('user_id', $membership->user_id)
            ->whereNull('ended_at')
            ->get()
            ->each(fn (BoardPositionAssignment $assignment) => $assignment->update(['ended_at' => now()]));
    }
}
