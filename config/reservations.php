<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Reservation Window Open Time
    |--------------------------------------------------------------------------
    |
    | Clock time (24-hour "H:i") at which the next day's reservation slots
    | unlock. This used to be implicitly midnight (00:00 - i.e. "24:00" of
    | the prior day) because the advance-booking window was calculated
    | purely from calendar dates, which meant every rollover happened the
    | instant the clock struck midnight and turned into a race between
    | residents (and bots - see the rate limiter comment in
    | AppServiceProvider). Moving this earlier spreads that rush out to
    | the evening instead.
    |
    */
    'window_open_time' => env('RESERVATION_WINDOW_OPEN_TIME', '20:00'),

    /*
    |--------------------------------------------------------------------------
    | Check-in/out Live Since
    |--------------------------------------------------------------------------
    |
    | When residents could start submitting check-in/out photos (the app
    | release with photo upload), Europe/Amsterdam time, e.g.
    | "2026-10-30 12:00". Until it's set, app:auto-reject-missed-checkouts
    | does nothing - see that command.
    |
    */
    'checks_live_since' => env('RESERVATION_CHECKS_LIVE_SINCE'),
];
