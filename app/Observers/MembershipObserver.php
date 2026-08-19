<?php

namespace App\Observers;

use App\Mail\MembershipStatusChangedMail;
use App\Models\Membership;
use Illuminate\Support\Facades\Mail;

class MembershipObserver
{
    public function updated(Membership $membership): void
    {
        if (! $membership->wasChanged('status')) {
            return;
        }

        Mail::to($membership->user)->send(new MembershipStatusChangedMail($membership));
    }
}
