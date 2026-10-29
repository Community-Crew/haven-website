<?php

namespace App\Mail;

use App\Mail\Support\RendersInlinedMail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * The one mail a former resident still gets (see GuardOutgoingMail): tells
 * them when their data will be anonymized, and who to reply to if they were
 * marked as moved out by mistake. Listed in mail.breaker.bulk_mailables,
 * since a Vestide import or a bulk action legitimately sends a batch.
 */
class ResidentMovedOutMail extends Mailable
{
    use Queueable, RendersInlinedMail, SerializesModels;

    public function __construct(public User $user) {}

    public function build(): self
    {
        $data = [
            'user' => $this->user,
            'date' => $this->user->anonymizesAt()->locale($this->user->locale ?? 'nl')->translatedFormat('j F Y'),
        ];

        return $this->subject(__('mail.resident_moved_out.subject'))
            ->replyTo(config('mail.contact_address'))
            ->html($this->renderInlined('mail.resident-moved-out', $data))
            ->text('mail.resident-moved-out-text', $data);
    }
}
