<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:cleanup-unused-media')->daily();

// There's no persistent queue worker process in production (QUEUE_CONNECTION
// is 'database', nothing consumes it otherwise) - this piggybacks on
// Laravel's scheduler (already cron'd, per the task above) to drain
// whatever's queued roughly once a minute instead. Fine for this app's
// current mail volume (e.g. PrivacyPolicyUpdatedMail); revisit with a real
// worker (systemd/supervisor) if queued volume grows enough that a
// once-a-minute batch isn't fast enough.
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->withoutOverlapping();
