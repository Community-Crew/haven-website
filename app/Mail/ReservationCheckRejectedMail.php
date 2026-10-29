<?php

namespace App\Mail;

use App\Mail\Support\RendersInlinedMail;
use App\Models\ReservationCheck;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReservationCheckRejectedMail extends Mailable
{
    use Queueable, RendersInlinedMail, SerializesModels;

    public function __construct(public ReservationCheck $check) {}

    public function build(): self
    {
        return $this->subject(__('mail.reservation_check_rejected.subject', ['room' => $this->check->reservation->room->name]))
            ->html($this->renderInlined('mail.reservation-check-rejected', ['check' => $this->check]))
            ->text('mail.reservation-check-rejected-text', ['check' => $this->check]);
    }
}
