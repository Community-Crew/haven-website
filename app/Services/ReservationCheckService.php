<?php

namespace App\Services;

use App\Enums\CheckType;
use App\Enums\CheckVerificationStatus;
use App\Enums\ReservationStatus;
use App\Mail\ReservationCheckRejectedMail;
use App\Models\Media;
use App\Models\Reservation;
use App\Models\ReservationCheck;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Ownership of the reservation is checked by the caller (Gate::authorize
 * against ReservationPolicy::update, same as ReservationCancelController) -
 * everything here assumes the acting user is already allowed to act on this
 * reservation.
 */
class ReservationCheckService
{
    /**
     * Minutes of slack either side of the reservation's start/end before a
     * check-in/check-out is flagged for admin attention. Flagging never
     * blocks the submission or changes its verification_status - every
     * submission still needs an explicit admin approve/reject, flagging
     * only affects how the admin queue is ordered.
     */
    protected const GRACE_MINUTES = 15;

    /**
     * A check-out is only possible until this many hours after the
     * reservation ends - past that, no photo is accepted at all and
     * AutoRejectMissedCheckouts assumes the space was left in a mess.
     * Check-in's own window is [start_at - GRACE_MINUTES, end_at]: it
     * reuses GRACE_MINUTES as its early-opening allowance rather than a
     * separate constant, and end_at is its hard cutoff on the late side.
     */
    public const CHECK_OUT_GRACE_HOURS = 1;

    /**
     * @param  array<int, UploadedFile>  $photos
     */
    public function checkIn(Reservation $reservation, array $photos): ReservationCheck
    {
        return DB::transaction(function () use ($reservation, $photos) {
            $this->guardCanSubmit($reservation, CheckType::CHECK_IN);

            return $this->createCheck($reservation, CheckType::CHECK_IN, $photos, $reservation->start_at);
        });
    }

    /**
     * @param  array<int, UploadedFile>  $photos
     */
    public function checkOut(Reservation $reservation, array $photos): ReservationCheck
    {
        return DB::transaction(function () use ($reservation, $photos) {
            $this->guardCanSubmit($reservation, CheckType::CHECK_OUT);

            if (! $reservation->checkIn()->exists()) {
                throw ValidationException::withMessages([
                    'photos' => 'You need to check in before you can check out.',
                ]);
            }

            return $this->createCheck($reservation, CheckType::CHECK_OUT, $photos, $reservation->end_at);
        });
    }

    /**
     * Shared by the admin review queue and the checks table so both record a
     * review identically. Authorization (ReservationCheckPolicy::update) is
     * the caller's job, same as for the resident-facing methods above.
     */
    public function approve(ReservationCheck $check, User $reviewer): void
    {
        $check->update([
            'verification_status' => CheckVerificationStatus::VERIFIED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'rejection_note' => null,
        ]);
    }

    /**
     * Also emails the resident, since a rejection is something they need to
     * act on (and $note is written to be read by them).
     */
    public function reject(ReservationCheck $check, User $reviewer, string $note): void
    {
        $check->update([
            'verification_status' => CheckVerificationStatus::REJECTED,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'rejection_note' => $note,
        ]);

        Mail::to($check->reservation->user)->send(new ReservationCheckRejectedMail($check));
    }

    protected function guardCanSubmit(Reservation $reservation, CheckType $type): void
    {
        if ($reservation->status !== ReservationStatus::APPROVED) {
            throw ValidationException::withMessages([
                'reservation' => 'Only approved reservations can be checked in or out.',
            ]);
        }

        if ($reservation->checks()->where('type', $type)->exists()) {
            throw ValidationException::withMessages([
                'photos' => $type === CheckType::CHECK_IN
                    ? 'This reservation has already been checked in.'
                    : 'This reservation has already been checked out.',
            ]);
        }

        if ($type === CheckType::CHECK_IN) {
            $opensAt = $reservation->start_at->copy()->subMinutes(self::GRACE_MINUTES);

            if (now()->lt($opensAt)) {
                throw ValidationException::withMessages([
                    'photos' => sprintf('You can check in starting %d minutes before your reservation begins.', self::GRACE_MINUTES),
                ]);
            }
        }

        $deadline = $type === CheckType::CHECK_IN
            ? $reservation->end_at
            : $reservation->end_at->copy()->addHours(self::CHECK_OUT_GRACE_HOURS);

        if (now()->gt($deadline)) {
            throw ValidationException::withMessages([
                'photos' => $type === CheckType::CHECK_IN
                    ? 'This reservation has already ended - you can no longer check in.'
                    : 'The check-out window for this reservation has closed.',
            ]);
        }
    }

    /**
     * @param  array<int, UploadedFile>  $photos
     */
    protected function createCheck(Reservation $reservation, CheckType $type, array $photos, Carbon $anchor): ReservationCheck
    {
        $occurredAt = now();

        $check = ReservationCheck::create([
            'reservation_id' => $reservation->id,
            'type' => $type,
            'user_id' => auth()->id(),
            'occurred_at' => $occurredAt,
            'verification_status' => CheckVerificationStatus::PENDING,
            'flag_reason' => $this->flagReason($type, $occurredAt, $anchor),
        ]);

        foreach ($photos as $photo) {
            $media = Media::createFromUploadedFile($photo, ReservationCheck::PHOTO_COLLECTION);

            $media->update([
                'mediable_type' => $check->getMorphClass(),
                'mediable_id' => $check->id,
            ]);
        }

        return $check;
    }

    /**
     * Non-blocking heuristic: null unless occurred_at falls outside a
     * GRACE_MINUTES window around $anchor (the reservation's start_at for a
     * check-in, end_at for a check-out). Uses raw timestamps rather than
     * Carbon's diffInMinutes() to avoid depending on that method's
     * sign convention.
     */
    protected function flagReason(CheckType $type, Carbon $occurredAt, Carbon $anchor): ?string
    {
        $deltaMinutes = (int) round(($occurredAt->getTimestamp() - $anchor->getTimestamp()) / 60);

        if (abs($deltaMinutes) <= self::GRACE_MINUTES) {
            return null;
        }

        return match ($type) {
            CheckType::CHECK_IN => $deltaMinutes < 0
                ? sprintf('Checked in %d min before the reservation start.', abs($deltaMinutes))
                : sprintf('Checked in %d min after the reservation start.', $deltaMinutes),
            CheckType::CHECK_OUT => $deltaMinutes < 0
                ? sprintf('Checked out %d min before the reservation end.', abs($deltaMinutes))
                : sprintf('Checked out %d min after the reservation end.', $deltaMinutes),
        };
    }
}
