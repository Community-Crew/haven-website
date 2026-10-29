<?php

use App\Enums\CheckVerificationStatus;
use App\Mail\ReservationCheckRejectedMail;
use App\Models\Reservation;
use App\Models\ReservationCheck;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->user = User::factory()->create([
        'email' => 'missed-checkout@example.com',
        'keycloak_id' => 'kc-missed-checkout',
        'locale' => 'nl',
    ]);

    $this->room = Room::create([
        'name' => 'Werkplaats',
        'slug' => 'werkplaats-missed-checkout',
        'description' => 'A room',
        'location' => 'Ground floor',
        'status' => 'available',
    ]);
});

it('auto-rejects a check-out once the grace period has passed with nothing submitted', function () {
    Mail::fake();

    $reservation = Reservation::create([
        'name' => 'Werkplaats sessie',
        'start_at' => now()->subHours(4),
        'end_at' => now()->subHours(2),
        'status' => 'approved',
        'user_id' => $this->user->id,
        'room_id' => $this->room->id,
    ]);

    $this->artisan('app:auto-reject-missed-checkouts');

    $check = ReservationCheck::where('reservation_id', $reservation->id)
        ->where('type', 'check_out')
        ->sole();

    expect($check->verification_status)->toBe(CheckVerificationStatus::REJECTED)
        ->and($check->reviewed_by)->toBeNull()
        ->and($check->rejection_note)->not->toBeNull();

    Mail::assertSent(
        ReservationCheckRejectedMail::class,
        fn (ReservationCheckRejectedMail $mail) => $mail->hasTo($this->user->email)
    );
});

it('leaves a reservation alone while it is still within the grace period', function () {
    $reservation = Reservation::create([
        'name' => 'Werkplaats sessie',
        'start_at' => now()->subHours(2),
        'end_at' => now()->subMinutes(30),
        'status' => 'approved',
        'user_id' => $this->user->id,
        'room_id' => $this->room->id,
    ]);

    $this->artisan('app:auto-reject-missed-checkouts');

    expect(ReservationCheck::where('reservation_id', $reservation->id)->exists())->toBeFalse();
});

it('does not auto-reject a reservation that already has a check-out', function () {
    $reservation = Reservation::create([
        'name' => 'Werkplaats sessie',
        'start_at' => now()->subHours(4),
        'end_at' => now()->subHours(2),
        'status' => 'approved',
        'user_id' => $this->user->id,
        'room_id' => $this->room->id,
    ]);

    ReservationCheck::create([
        'reservation_id' => $reservation->id,
        'type' => 'check_out',
        'user_id' => $this->user->id,
        'occurred_at' => now()->subHours(2),
        'verification_status' => CheckVerificationStatus::PENDING,
    ]);

    $this->artisan('app:auto-reject-missed-checkouts');

    expect(ReservationCheck::where('reservation_id', $reservation->id)->count())->toBe(1);
});

it('ignores reservations that are not approved', function () {
    $reservation = Reservation::create([
        'name' => 'Werkplaats sessie',
        'start_at' => now()->subHours(4),
        'end_at' => now()->subHours(2),
        'status' => 'cancelled',
        'user_id' => $this->user->id,
        'room_id' => $this->room->id,
    ]);

    $this->artisan('app:auto-reject-missed-checkouts');

    expect(ReservationCheck::where('reservation_id', $reservation->id)->exists())->toBeFalse();
});
