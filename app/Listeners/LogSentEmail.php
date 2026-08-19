<?php

namespace App\Listeners;

use App\Models\SentEmail;
use App\Models\User;
use Illuminate\Mail\Events\MessageSent;

/**
 * Records every mail the app sends to sent_emails, regardless of which
 * Mailable sent it - fires on Laravel's own MessageSent event rather than
 * being called from each Mailable, so new mail types (including future
 * mailing-list sends) get logged automatically without extra wiring.
 */
class LogSentEmail
{
    public function handle(MessageSent $event): void
    {
        $message = $event->sent->getOriginalMessage();

        $to = collect($message->getTo())->first()?->getAddress();

        if (! $to) {
            return;
        }

        SentEmail::create([
            'user_id' => User::whereEmail($to)->value('id'),
            'mailable' => $event->data['__laravel_mailable'] ?? 'unknown',
            'to' => $to,
            'subject' => $message->getSubject() ?? '',
            'locale' => app()->getLocale(),
            'sent_at' => now(),
        ]);
    }
}
