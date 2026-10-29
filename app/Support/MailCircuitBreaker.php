<?php

namespace App\Support;

use App\Models\SentEmail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Last line of defence against a bug mass-mailing residents (as happened on
 * 2026-10-29, when a new scheduled job emailed a rejection for every past
 * reservation). Checked by App\Listeners\GuardOutgoingMail before every send,
 * counting from sent_emails (what LogSentEmail records), so it covers any
 * code path - controllers, observers, scheduled jobs, queued mail - without
 * each one having to opt in.
 *
 * Two limits:
 * - Global: more than max_per_window non-bulk mails within window_minutes
 *   trips the breaker - every further send is dropped for cooldown_hours (or
 *   until `php artisan app:mail-breaker --reset`), and an alert goes out.
 * - Per recipient: more than max_per_recipient_per_hour to one address drops
 *   just that mail (logged), without tripping anything.
 *
 * Mailables listed in mail.breaker.bulk_mailables (deliberate one-to-everyone
 * sends, like the privacy policy update) skip the global limit but still
 * respect the per-recipient one.
 */
class MailCircuitBreaker
{
    protected const TRIPPED_KEY = 'mail-breaker:tripped-at';

    /**
     * Set while the breaker sends its own alert, so that mail isn't blocked
     * by the very breaker it's reporting on.
     */
    protected static bool $sendingAlert = false;

    /**
     * @return bool whether the mail may be sent
     */
    public function allows(string $mailable, ?string $recipient): bool
    {
        if (static::$sendingAlert || ! config('mail.breaker.enabled')) {
            return true;
        }

        if ($trippedAt = $this->trippedAt()) {
            Log::warning('Mail circuit breaker: dropped mail while tripped', [
                'mailable' => $mailable,
                'to' => $recipient,
                'tripped_at' => $trippedAt->toIso8601String(),
            ]);

            return false;
        }

        if ($recipient && $this->recentToRecipient($recipient) >= config('mail.breaker.max_per_recipient_per_hour')) {
            Log::warning('Mail circuit breaker: dropped mail over the per-recipient limit', [
                'mailable' => $mailable,
                'to' => $recipient,
            ]);

            return false;
        }

        if (! $this->isBulk($mailable) && $this->recentNonBulk() >= config('mail.breaker.max_per_window')) {
            $this->trip($mailable);

            return false;
        }

        return true;
    }

    public function trippedAt(): ?Carbon
    {
        $value = Cache::get(self::TRIPPED_KEY);

        return $value ? Carbon::parse($value) : null;
    }

    public function reset(): void
    {
        Cache::forget(self::TRIPPED_KEY);
    }

    public function isBulk(string $mailable): bool
    {
        return in_array($mailable, config('mail.breaker.bulk_mailables', []), true);
    }

    public function recentNonBulk(): int
    {
        return SentEmail::query()
            ->where('sent_at', '>=', now()->subMinutes(config('mail.breaker.window_minutes')))
            ->whereNotIn('mailable', config('mail.breaker.bulk_mailables', []))
            ->count();
    }

    protected function recentToRecipient(string $recipient): int
    {
        return SentEmail::query()
            ->where('to', $recipient)
            ->where('sent_at', '>=', now()->subHour())
            ->count();
    }

    protected function trip(string $mailable): void
    {
        $now = now();
        Cache::put(self::TRIPPED_KEY, $now->toIso8601String(), now()->addHours(config('mail.breaker.cooldown_hours')));

        $summary = sprintf(
            'More than %d emails were sent in the last %d minutes, so all outgoing mail is paused for %d hours (until %s). The mail that tripped it was %s. Check storage/logs/laravel.log and the sent_emails table for what was sending, then run `php artisan app:mail-breaker --reset` once it is safe to resume.',
            config('mail.breaker.max_per_window'),
            config('mail.breaker.window_minutes'),
            config('mail.breaker.cooldown_hours'),
            $now->copy()->addHours(config('mail.breaker.cooldown_hours'))->format('d/m/Y H:i'),
            $mailable,
        );

        Log::critical('Mail circuit breaker tripped: '.$summary);

        $this->sendAlert($summary);
    }

    protected function sendAlert(string $summary): void
    {
        $to = config('mail.breaker.alert_to');

        if (! $to) {
            return;
        }

        static::$sendingAlert = true;

        try {
            Mail::raw($summary, fn ($message) => $message->to($to)->subject('[Haven] Outgoing mail paused by the circuit breaker'));
        } catch (Throwable $e) {
            Log::error('Mail circuit breaker: could not send the alert', ['exception' => $e->getMessage()]);
        } finally {
            static::$sendingAlert = false;
        }
    }
}
