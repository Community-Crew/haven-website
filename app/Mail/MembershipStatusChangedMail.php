<?php

namespace App\Mail;

use App\Mail\Support\RendersInlinedMail;
use App\Models\Membership;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MembershipStatusChangedMail extends Mailable
{
    use Queueable, RendersInlinedMail, SerializesModels;

    public function __construct(public Membership $membership) {}

    public function build(): self
    {
        $status = __('mail.membership_status_changed.status.'.$this->membership->status->value);

        return $this->subject(__('mail.membership_status_changed.subject', ['status' => $status]))
            ->html($this->renderInlined('mail.membership-status-changed', ['membership' => $this->membership]))
            ->text('mail.membership-status-changed-text', ['membership' => $this->membership]);
    }
}
