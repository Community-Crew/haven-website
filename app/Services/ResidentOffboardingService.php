<?php

namespace App\Services;

use App\Enums\MembershipStatus;
use App\Mail\ResidentMovedOutMail;
use App\Models\Media;
use App\Models\Membership;
use App\Models\Reservation;
use App\Models\ReservationCheck;
use App\Models\ReservationNote;
use App\Models\SentEmail;
use App\Models\User;
use App\Services\Keycloak\KeycloakAdminService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Spatie\Activitylog\Models\Activity;

/**
 * A resident's way out: markMovedOut() (from a Vestide import or an admin)
 * detaches them from their unit and starts a RETENTION_DAYS grace period;
 * once that's over, app:anonymize-former-residents calls anonymize() to wipe
 * their personal data. In between they're a "former resident" - the only
 * mail they still get is the move-out notice (see GuardOutgoingMail).
 *
 * Anonymize rather than delete: reservations stay (minus anything personal)
 * so occupancy statistics remain correct, but nothing left can identify the
 * person.
 */
class ResidentOffboardingService
{
    /**
     * Long enough to sort out a dispute about a last reservation/check-out,
     * or a mistaken Vestide match, before the data is gone for good.
     */
    public const RETENTION_DAYS = 30;

    public function __construct(protected KeycloakAdminService $keycloak) {}

    /**
     * No-op for someone already moved out or anonymized. Returns whether
     * anything changed, so callers only notify people who were actually
     * moved out just now.
     */
    public function markMovedOut(User $user): bool
    {
        if ($user->isFormerResident()) {
            return false;
        }

        DB::transaction(function () use ($user) {
            // Mark first: ending a membership below fires
            // MembershipStatusChangedMail via MembershipObserver, which the
            // mail guard then drops because they're a former resident.
            $user->update([
                'moved_out_at' => now(),
                'unit_id' => null,
                'activated_at' => null,
            ]);

            // Ending through the model (not a bulk update) so
            // MembershipObserver also ends their board positions.
            $user->memberships()
                ->whereIn('status', MembershipStatus::open())
                ->get()
                ->each(fn (Membership $membership) => $membership->update(['status' => MembershipStatus::ENDED]));
        });

        return true;
    }

    public function sendMovedOutNotice(User $user): void
    {
        Mail::to($user)->send(new ResidentMovedOutMail($user));
    }

    /**
     * Only possible before anonymization. Doesn't re-activate them - they
     * still need a registration code (or an admin's "Activate Now") for a
     * unit, same as anyone else.
     */
    public function undoMoveOut(User $user): void
    {
        if ($user->anonymized_at) {
            return;
        }

        $user->update(['moved_out_at' => null]);
    }

    /**
     * Irreversible. The Keycloak account goes first: if that fails nothing
     * local has been touched yet, and the daily run simply retries.
     */
    public function anonymize(User $user): void
    {
        if ($user->anonymized_at) {
            return;
        }

        if ($user->keycloak_id) {
            $this->keycloak->deleteUser($user->keycloak_id);
        }

        // Every model here is Auditable - logging stays off throughout, or
        // the anonymizing update itself would write the old name/email
        // straight back into activity_log.
        activity()->disableLogging();

        try {
            DB::transaction(fn () => $this->wipe($user));
        } finally {
            activity()->enableLogging();
        }
    }

    protected function wipe(User $user): void
    {
        $reservationIds = $user->reservations()->pluck('id');
        $checkIds = ReservationCheck::whereIn('reservation_id', $reservationIds)->pluck('id');
        $noteIds = ReservationNote::whereIn('reservation_id', $reservationIds)->pluck('id');
        $userMorph = $user->getMorphClass();

        // Check-in/out photos show the room as they left it, possibly with
        // them or their belongings in shot.
        Media::query()
            ->where('mediable_type', (new ReservationCheck)->getMorphClass())
            ->whereIn('mediable_id', $checkIds)
            ->get()
            ->each(function (Media $media) {
                $media->deleteFromDisk();
                $media->delete();
            });

        // Admin notes are written about the resident.
        ReservationNote::whereKey($noteIds)->delete();

        // A reservation's title is free text the resident typed in.
        Reservation::whereKey($reservationIds)->update(['name' => null, 'share_user' => false]);

        SentEmail::query()
            ->where('user_id', $user->id)
            ->orWhere('to', $user->email)
            ->delete();

        Activity::query()
            ->where(fn ($query) => $query->where('causer_type', $userMorph)->where('causer_id', $user->id))
            ->orWhere(fn ($query) => $query->where('subject_type', $userMorph)->where('subject_id', $user->id))
            ->orWhere(fn ($query) => $query->where('subject_type', (new Reservation)->getMorphClass())->whereIn('subject_id', $reservationIds))
            ->orWhere(fn ($query) => $query->where('subject_type', (new ReservationCheck)->getMorphClass())->whereIn('subject_id', $checkIds))
            ->orWhere(fn ($query) => $query->where('subject_type', (new ReservationNote)->getMorphClass())->whereIn('subject_id', $noteIds))
            ->delete();

        $user->groups()->detach();
        $user->organisations()->detach();
        $user->roles()->detach();

        $user->forceFill([
            'name' => "Former resident #{$user->id}",
            'email' => "former-resident-{$user->id}@anonymized.invalid",
            // NOT NULL + unique, so a placeholder rather than null - the
            // real Keycloak account is gone, so nothing can log in as it.
            'keycloak_id' => "anonymized-{$user->id}",
            'keycloak_token' => null,
            'keycloak_refresh_token' => null,
            'remember_token' => null,
            'unit_id' => null,
            'activated_at' => null,
            'privacy_policy_accepted_at' => null,
            'moved_out_at' => $user->moved_out_at ?? now(),
            'anonymized_at' => now(),
        ])->save();
    }
}
