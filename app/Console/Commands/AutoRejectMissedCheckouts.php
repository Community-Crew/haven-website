<?php

namespace App\Console\Commands;

use App\Enums\CheckType;
use App\Enums\CheckVerificationStatus;
use App\Enums\ReservationStatus;
use App\Mail\ReservationCheckRejectedMail;
use App\Models\Reservation;
use App\Models\ReservationCheck;
use App\Services\ReservationCheckService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

/**
 * A check-out submitted more than CHECK_OUT_GRACE_HOURS after a reservation
 * ends is refused outright by ReservationCheckService - but a resident who
 * never submits one at all would otherwise just leave a silent gap. Once
 * that same grace window has passed with no check-out on record, this
 * assumes the worst (room left in a mess) and auto-creates a rejected
 * ReservationCheck so it surfaces the same way an admin-rejected one would,
 * rather than needing an admin to go looking for reservations with no
 * check-out.
 */
class AutoRejectMissedCheckouts extends Command
{
    protected $signature = 'app:auto-reject-missed-checkouts';

    protected $description = 'Auto-reject the check-out for approved reservations whose 1-hour post-end grace period has passed with no check-out submitted';

    /**
     * When residents could actually start submitting check-outs, i.e. when
     * the app version with photo upload went live - set via
     * RESERVATION_CHECKS_LIVE_SINCE (e.g. "2026-10-30 12:00", Amsterdam
     * time). Unset means the job does nothing: auto-rejecting a check-out
     * nobody could submit just emails residents a wrong rejection, which is
     * what happened twice on 2026-10-29 (first for every past reservation,
     * then because the backend shipped before the app did).
     */
    /**
     * Only reservations that ended within this window are considered, so a
     * scheduler outage (or a future bug) can't turn into a flood of emails
     * about long-past reservations - an hour or so late is fine, days late
     * is just noise.
     */
    protected const LOOKBACK_HOURS = 24;

    public static function liveSince(): ?Carbon
    {
        $value = config('reservations.checks_live_since');

        return $value ? Carbon::parse($value, 'Europe/Amsterdam') : null;
    }

    public function handle(): void
    {
        if (! self::liveSince()) {
            $this->info('Check-out auto-rejection is off - RESERVATION_CHECKS_LIVE_SINCE is not set.');

            return;
        }

        $deadline = now()->subHours(ReservationCheckService::CHECK_OUT_GRACE_HOURS);
        $earliest = max(self::liveSince(), $deadline->copy()->subHours(self::LOOKBACK_HOURS));

        $reservations = Reservation::query()
            ->where('status', ReservationStatus::APPROVED)
            ->where('end_at', '<', $deadline)
            ->where('end_at', '>=', $earliest)
            ->whereDoesntHave('checks', fn ($query) => $query->where('type', CheckType::CHECK_OUT))
            ->with(['user', 'room'])
            ->get();

        if ($reservations->isEmpty()) {
            $this->info('No missed check-outs to auto-reject.');

            return;
        }

        foreach ($reservations as $reservation) {
            $check = ReservationCheck::create([
                'reservation_id' => $reservation->id,
                'type' => CheckType::CHECK_OUT,
                'user_id' => $reservation->user_id,
                'occurred_at' => $reservation->end_at,
                'verification_status' => CheckVerificationStatus::REJECTED,
                'reviewed_at' => now(),
                'rejection_note' => 'No check-out was submitted within 1 hour of the reservation ending, so the room is assumed not to have been left in an acceptable state.',
            ]);

            Mail::to($reservation->user)->send(new ReservationCheckRejectedMail($check));
        }

        $this->info("Auto-rejected {$reservations->count()} missed check-out(s).");
    }
}
