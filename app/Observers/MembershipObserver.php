<?php

namespace App\Observers;

use App\Mail\MembershipStatusChangedMail;
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
        }
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
