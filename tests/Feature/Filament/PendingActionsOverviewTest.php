<?php

use App\Enums\CheckVerificationStatus;
use App\Filament\Widgets\PendingActionsOverview;
use App\Models\Reservation;
use App\Models\ReservationCheck;
use App\Models\Room;
use App\Models\User;

it('counts pending reservations and pending/flagged checks', function () {
    $user = User::factory()->create(['email' => 'stats-user@example.com', 'keycloak_id' => 'kc-stats-user']);
    $room = Room::create([
        'name' => 'Werkplaats',
        'slug' => 'werkplaats-stats',
        'description' => 'A room',
        'location' => 'Ground floor',
        'status' => 'available',
    ]);

    Reservation::create([
        'name' => 'Pending one', 'start_at' => now(), 'end_at' => now()->addHour(),
        'status' => 'pending', 'user_id' => $user->id, 'room_id' => $room->id,
    ]);
    $approved = Reservation::create([
        'name' => 'Approved one', 'start_at' => now(), 'end_at' => now()->addHour(),
        'status' => 'approved', 'user_id' => $user->id, 'room_id' => $room->id,
    ]);

    ReservationCheck::create([
        'reservation_id' => $approved->id, 'type' => 'check_in', 'user_id' => $user->id,
        'occurred_at' => now(), 'verification_status' => CheckVerificationStatus::PENDING,
        'flag_reason' => 'Checked in 42 min before the reservation start.',
    ]);

    $widget = new PendingActionsOverview;
    $stats = (fn () => $this->getStats())->call($widget);

    expect($stats[0]->getValue())->toBe(1)
        ->and($stats[1]->getValue())->toBe(1)
        ->and($stats[1]->getDescription())->toBe('1 flagged');
});

it('counts recent auto-rejected missed check-outs but not admin-reviewed or old ones', function () {
    $user = User::factory()->create(['email' => 'stats-user2@example.com', 'keycloak_id' => 'kc-stats-user2']);
    $admin = User::factory()->create(['email' => 'stats-admin@example.com', 'keycloak_id' => 'kc-stats-admin']);
    $room = Room::create([
        'name' => 'Werkplaats',
        'slug' => 'werkplaats-stats2',
        'description' => 'A room',
        'location' => 'Ground floor',
        'status' => 'available',
    ]);

    $reservation = Reservation::create([
        'name' => 'Reservation', 'start_at' => now()->subHours(4), 'end_at' => now()->subHours(3),
        'status' => 'approved', 'user_id' => $user->id, 'room_id' => $room->id,
    ]);

    // The one that should count: system-rejected, recent.
    ReservationCheck::create([
        'reservation_id' => $reservation->id, 'type' => 'check_out', 'user_id' => $user->id,
        'occurred_at' => now(), 'verification_status' => CheckVerificationStatus::REJECTED,
        'reviewed_by' => null,
    ]);

    // Admin-reviewed rejection shouldn't count as "missed".
    $reservationTwo = Reservation::create([
        'name' => 'Reservation 2', 'start_at' => now()->subHours(4), 'end_at' => now()->subHours(3),
        'status' => 'approved', 'user_id' => $user->id, 'room_id' => $room->id,
    ]);
    ReservationCheck::create([
        'reservation_id' => $reservationTwo->id, 'type' => 'check_out', 'user_id' => $user->id,
        'occurred_at' => now(), 'verification_status' => CheckVerificationStatus::REJECTED,
        'reviewed_by' => $admin->id,
    ]);

    // Old auto-rejection shouldn't count either.
    $reservationThree = Reservation::create([
        'name' => 'Reservation 3', 'start_at' => now()->subDays(30), 'end_at' => now()->subDays(30),
        'status' => 'approved', 'user_id' => $user->id, 'room_id' => $room->id,
    ]);
    $old = ReservationCheck::create([
        'reservation_id' => $reservationThree->id, 'type' => 'check_out', 'user_id' => $user->id,
        'occurred_at' => now(), 'verification_status' => CheckVerificationStatus::REJECTED,
        'reviewed_by' => null,
    ]);
    $old->forceFill(['created_at' => now()->subDays(30)])->saveQuietly();

    $widget = new PendingActionsOverview;
    $stats = (fn () => $this->getStats())->call($widget);

    expect($stats[2]->getValue())->toBe(1);
});
