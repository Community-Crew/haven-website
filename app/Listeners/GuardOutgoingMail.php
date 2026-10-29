<?php

namespace App\Listeners;

use App\Mail\ResidentMovedOutMail;
use App\Models\User;
use App\Support\MailCircuitBreaker;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Log;

/**
 * Runs before every outbound mail - returning false from a MessageSending
 * listener makes Laravel's Mailer skip the send silently, so callers don't
 * need to handle a blocked mail. Auto-discovered, same as LogSentEmail.
 *
 * Two checks: former residents (moved out or anonymized) only ever get the
 * move-out notice - whatever else tries to mail them, e.g. a job acting on
 * their old reservations - and then the MailCircuitBreaker's volume limits.
 */
class GuardOutgoingMail
{
    /**
     * The only mail still allowed to reach a former resident.
     */
    protected const FORMER_RESIDENT_ALLOWED = [
        ResidentMovedOutMail::class,
    ];

    public function __construct(protected MailCircuitBreaker $breaker) {}

    public function handle(MessageSending $event): ?bool
    {
        $mailable = $event->data['__laravel_mailable'] ?? 'unknown';
        $recipient = collect($event->message->getTo())->first()?->getAddress();

        if ($recipient && ! in_array($mailable, self::FORMER_RESIDENT_ALLOWED, true)
            && User::formerResidents()->where('email', $recipient)->exists()) {
            Log::info('Dropped mail to a former resident', ['mailable' => $mailable, 'to' => $recipient]);

            return false;
        }

        return $this->breaker->allows($mailable, $recipient) ? null : false;
    }
}
