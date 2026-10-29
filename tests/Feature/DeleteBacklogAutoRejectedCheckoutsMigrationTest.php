<?php

use App\Enums\CheckVerificationStatus;
use App\Models\Reservation;
use App\Models\ReservationCheck;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Carbon;

it('deletes only the backlog auto-rejections from before check-in/out went live', function () {
    $live = Carbon::parse('2026-10-29 14:11:31', 'Europe/Amsterdam');
    $admin = User::factory()->create(['email' => 'cleanup-admin@example.com', 'keycloak_id' => 'kc-cleanup-admin']);
    $resident = User::factory()->create(['email' => 'cleanup-resident@example.com', 'keycloak_id' => 'kc-cleanup-resident']);
    $room = Room::create(['name' => 'Werkplaats', 'slug' => 'werkplaats-cleanup', 'description' => 'A room', 'location' => 'Ground floor', 'status' => 'available']);

    $reservation = fn ($endAt) => Reservation::create([
        'name' => 'Sessie', 'start_at' => $endAt->copy()->subHours(2), 'end_at' => $endAt,
        'status' => 'approved', 'user_id' => $resident->id, 'room_id' => $room->id,
    ]);
    $check = fn (Reservation $r, array $overrides = []) => ReservationCheck::create(array_merge([
        'reservation_id' => $r->id, 'type' => 'check_out', 'user_id' => $resident->id, 'occurred_at' => $r->end_at,
        'verification_status' => CheckVerificationStatus::REJECTED, 'rejection_note' => 'Auto',
    ], $overrides));

    $backlog = $check($reservation($live->copy()->subMonths(2)));
    $afterLaunch = $check($reservation($live->copy()->addHours(5)));
    $adminRejected = $check($reservation($live->copy()->subDay()), ['reviewed_by' => $admin->id]);
    $checkIn = $check($reservation($live->copy()->subDays(2)), ['type' => 'check_in']);

    (require database_path('migrations/2026_10_29_150000_delete_backlog_auto_rejected_checkouts.php'))->up();

    expect(ReservationCheck::find($backlog->id))->toBeNull()
        ->and(ReservationCheck::find($afterLaunch->id))->not->toBeNull()
        ->and(ReservationCheck::find($adminRejected->id))->not->toBeNull()
        ->and(ReservationCheck::find($checkIn->id))->not->toBeNull();
});
