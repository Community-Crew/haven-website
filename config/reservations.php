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

    /*
    |--------------------------------------------------------------------------
    | Proof of Work
    |--------------------------------------------------------------------------
    |
    | Making a reservation needs a solved challenge (see App\Services\
    | ProofOfWork): the app fetches one when the booking form opens and
    | solves it in the background while the resident fills the form in. That
    | makes booking through the API without the page - or in bulk from a
    | script - expensive. Difficulty is in leading zero bits of a SHA-256
    | hash: each extra bit doubles the work, 16 is roughly a second on a
    | phone. 0 switches the check off.
    |
    | min_age: no one fills the form in faster than this, so a challenge
    | younger than it is refused. max_age: how long one stays valid - the app
    | fetches and solves a fresh one in the background before it runs out.
    |
    */
    'proof_of_work' => [
        'difficulty' => (int) env('RESERVATION_POW_DIFFICULTY', 16),
        'min_age' => (int) env('RESERVATION_POW_MIN_AGE', 5),
        'max_age' => (int) env('RESERVATION_POW_MAX_AGE', 600),

        // Signals shown to admins (Reservation::bookingSignals()), never
        // enforced: a booking this many seconds or less after the challenge
        // was issued - faster than picking a slot and typing a name - and
        // one this many seconds or less after the window opened.
        'suspicious_age' => (int) env('RESERVATION_POW_SUSPICIOUS_AGE', 10),
        'window_rush' => (int) env('RESERVATION_WINDOW_RUSH_SECONDS', 60),
    ],
];
