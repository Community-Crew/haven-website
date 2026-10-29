<?php

namespace App\Console\Commands;

use App\Support\MailCircuitBreaker;
use Illuminate\Console\Command;

class MailBreaker extends Command
{
    protected $signature = 'app:mail-breaker {--reset : Resume outgoing mail after the breaker tripped}';

    protected $description = 'Show the outgoing-mail circuit breaker status, or reset it';

    public function handle(MailCircuitBreaker $breaker): void
    {
        if ($this->option('reset')) {
            $breaker->reset();
            $this->info('Mail circuit breaker reset - outgoing mail resumes.');

            return;
        }

        $trippedAt = $breaker->trippedAt();

        $this->line($trippedAt
            ? "<error>Tripped</error> at {$trippedAt->format('d/m/Y H:i')} - outgoing mail is paused."
            : '<info>OK</info> - outgoing mail is flowing.');

        $this->line(sprintf(
            'Non-bulk mails in the last %d minutes: %d / %d',
            config('mail.breaker.window_minutes'),
            $breaker->recentNonBulk(),
            config('mail.breaker.max_per_window'),
        ));
    }
}
