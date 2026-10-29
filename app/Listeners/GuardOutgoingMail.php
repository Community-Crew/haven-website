<?php

namespace App\Listeners;

use App\Support\MailCircuitBreaker;
use Illuminate\Mail\Events\MessageSending;

/**
 * Runs before every outbound mail - returning false from a MessageSending
 * listener makes Laravel's Mailer skip the send silently, so callers don't
 * need to handle a blocked mail. Auto-discovered, same as LogSentEmail.
 */
class GuardOutgoingMail
{
    public function __construct(protected MailCircuitBreaker $breaker) {}

    public function handle(MessageSending $event): ?bool
    {
        $recipient = collect($event->message->getTo())->first()?->getAddress();

        return $this->breaker->allows($event->data['__laravel_mailable'] ?? 'unknown', $recipient)
            ? null
            : false;
    }
}
