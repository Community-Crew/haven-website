<?php

namespace App\Mail;

use App\Mail\Support\RendersInlinedMail;
use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReservationCancelledMail extends Mailable
{
    use Queueable, RendersInlinedMail, SerializesModels;

    public function __construct(public Reservation $reservation) {}

    public function build(): self
    {
        return $this->subject(__('mail.reservation_cancelled.subject', ['room' => $this->reservation->room->name]))
            ->html($this->renderInlined('mail.reservation-cancelled', ['reservation' => $this->reservation]))
            ->text('mail.reservation-cancelled-text', ['reservation' => $this->reservation]);
    }
}
