<?php

use App\Enums\CheckVerificationStatus;
use App\Models\Reservation;
use App\Models\ReservationCheck;
use App\Models\Room;
use App\Models\User;

it('deletes every auto-rejection but keeps admin reviews and check-ins', function () {
    $admin = User::factory()->create(['email' => 'gap-admin@example.com', 'keycloak_id' => 'kc-gap-admin']);
    $resident = User::factory()->create(['email' => 'gap-resident@example.com', 'keycloak_id' => 'kc-gap-resident']);
    $room = Room::create(['name' => 'Werkplaats', 'slug' => 'werkplaats-gap', 'description' => 'A room', 'location' => 'Ground floor', 'status' => 'available']);

    $check = function (array $overrides) use ($resident, $room) {
        $reservation = Reservation::create([
            'name' => 'Sessie', 'start_at' => now()->subHours(3), 'end_at' => now()->subHours(2),
            'status' => 'approved', 'user_id' => $resident->id, 'room_id' => $room->id,
        ]);

        return ReservationCheck::create(array_merge([
            'reservation_id' => $reservation->id, 'type' => 'check_out', 'user_id' => $resident->id,
            'occurred_at' => $reservation->end_at, 'verification_status' => CheckVerificationStatus::REJECTED,
        ], $overrides));
    };

    $autoRejected = $check([]);
    $adminRejected = $check(['reviewed_by' => $admin->id]);
    $checkIn = $check(['type' => 'check_in', 'verification_status' => CheckVerificationStatus::PENDING]);

    (require database_path('migrations/2026_10_29_180000_delete_auto_rejections_before_app_release.php'))->up();

    expect(ReservationCheck::find($autoRejected->id))->toBeNull()
        ->and(ReservationCheck::find($adminRejected->id))->not->toBeNull()
        ->and(ReservationCheck::find($checkIn->id))->not->toBeNull();
});
