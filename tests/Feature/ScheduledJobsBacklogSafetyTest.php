<?php

use App\Console\Commands\AutoRejectMissedCheckouts;
use App\Enums\CheckVerificationStatus;
use App\Models\Media;
use App\Models\Membership;
use App\Models\MemberType;
use App\Models\Reservation;
use App\Models\ReservationCheck;
use App\Models\Room;
use App\Models\User;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/*
 * Guards against a repeat of 2026-10-29, when a newly scheduled job's first
 * production run treated every past reservation as fresh and emailed a
 * rejection for each one. Simulates deploying onto a database full of
 * history - nothing in it is recent - and runs *every* job registered in
 * routes/console.php: none of them may mail anyone about the past.
 *
 * Covers future scheduled jobs automatically. If one legitimately needs to
 * act on old data, give it an explicit cutoff/lookback (see
 * AutoRejectMissedCheckouts::LIVE_SINCE) rather than loosening this test.
 * When adding new kinds of data a job might act on, add a historic example
 * to seedHistory() below.
 */

function seedHistory(): void
{
    Http::fake([
        '*/protocol/openid-connect/token' => Http::response(['access_token' => 'fake-admin-token']),
        '*' => Http::response(),
    ]);
    Storage::fake('hetzner');

    $type = MemberType::create(['name' => 'Regular']);
    $room = Room::create([
        'name' => 'Werkplaats',
        'slug' => 'werkplaats-history',
        'description' => 'A room',
        'location' => 'Ground floor',
        'status' => 'available',
    ]);

    foreach (range(1, 5) as $i) {
        $user = User::factory()->create([
            'email' => "history-{$i}@example.com",
            'keycloak_id' => "kc-history-{$i}",
            'locale' => $i % 2 ? 'nl' : 'en',
            'activated_at' => now()->subYear(),
        ]);

        Membership::create(['user_id' => $user->id, 'member_type_id' => $type->id, 'status' => 'active']);

        // Reservations from a year ago up to two days ago, in every status,
        // with and without check-ins - none submitted a check-out.
        foreach ([now()->subYear(), now()->subMonths(2), now()->subDays(9), now()->subDays(2)] as $j => $endAt) {
            foreach (['approved', 'pending', 'cancelled', 'rejected'] as $status) {
                $reservation = Reservation::create([
                    'name' => "Sessie {$i}-{$j}-{$status}",
                    'start_at' => $endAt->copy()->subHours(2),
                    'end_at' => $endAt,
                    'status' => $status,
                    'user_id' => $user->id,
                    'room_id' => $room->id,
                ]);

                if ($status === 'approved' && $j % 2 === 0) {
                    ReservationCheck::create([
                        'reservation_id' => $reservation->id,
                        'type' => 'check_in',
                        'user_id' => $user->id,
                        'occurred_at' => $reservation->start_at,
                        'verification_status' => CheckVerificationStatus::PENDING,
                    ]);
                }
            }
        }
    }

    // Residents who moved out long ago, still waiting to be anonymized -
    // anonymize-former-residents acts on them, and must not mail them.
    foreach (range(1, 2) as $i) {
        User::factory()->create([
            'email' => "moved-out-{$i}@example.com",
            'keycloak_id' => "kc-moved-out-{$i}",
            'locale' => 'nl',
            'moved_out_at' => now()->subMonths(4),
        ]);
    }

    // Long-unlinked media, the kind cleanup-unused-media acts on.
    $orphan = Media::create(['disk' => 'hetzner', 'path' => 'media/old/orphan.webp', 'collection' => 'old']);
    Storage::disk('hetzner')->put($orphan->path, 'bytes');
    $orphan->forceFill(['updated_at' => now()->subMonths(3)])->saveQuietly();
}

/**
 * @return array<int, string> artisan command lines for every scheduled job
 */
function scheduledCommands(): array
{
    app(Kernel::class)->bootstrap();

    return collect(app(Schedule::class)->events())
        ->map(fn (Event $event) => trim(Str::after((string) $event->command, "'artisan'")))
        ->filter()
        ->values()
        ->all();
}

it('has scheduled jobs to check', function () {
    expect(scheduledCommands())->toContain('app:auto-reject-missed-checkouts');
});

it('mails nobody about historic data when every scheduled job runs right after a deploy', function () {
    // Long after check-in/out went live, so the backlog isn't excluded
    // merely because it predates the feature.
    $this->travelTo(AutoRejectMissedCheckouts::liveSince()->addYears(2));
    seedHistory();

    Mail::fake();

    foreach (scheduledCommands() as $command) {
        $this->artisan($command)->assertSuccessful();
    }

    Mail::assertNothingSent();
    Mail::assertNothingQueued();
});
