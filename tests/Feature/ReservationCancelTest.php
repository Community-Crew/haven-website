<?php

use App\Enums\CheckVerificationStatus;
use App\Http\Middleware\EnsureUserAcceptedPrivacyPolicy;
use App\Http\Middleware\EnsureUserIsActivated;
use App\Http\Middleware\ValidateKeycloakToken;
use App\Models\Reservation;
use App\Models\ReservationCheck;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->withoutMiddleware([
        ValidateKeycloakToken::class,
        EnsureUserAcceptedPrivacyPolicy::class,
        EnsureUserIsActivated::class,
    ]);

    Mail::fake();

    $this->user = User::factory()->create([
        'email' => 'canceller@example.com',
        'keycloak_id' => 'kc-canceller',
    ]);

    $this->room = Room::create([
        'name' => 'Werkplaats',
        'slug' => 'werkplaats-cancel',
        'description' => 'A room',
        'location' => 'Ground floor',
        'status' => 'available',
    ]);
});

it('cancels an approved reservation that has not been checked into yet', function () {
    $reservation = Reservation::create([
        'name' => 'Werkplaats sessie',
        'start_at' => now()->addHour(),
        'end_at' => now()->addHours(3),
        'status' => 'approved',
        'user_id' => $this->user->id,
        'room_id' => $this->room->id,
    ]);

    $this->actingAs($this->user)
        ->patchJson("/api/v1/reservations/{$reservation->id}/cancel")
        ->assertStatus(200);

    expect($reservation->fresh()->status->value)->toBe('cancelled');
});

it('refuses to cancel a reservation that has already been checked into', function () {
    $reservation = Reservation::create([
        'name' => 'Werkplaats sessie',
        'start_at' => now()->addMinutes(10),
        'end_at' => now()->addHours(2),
        'status' => 'approved',
        'user_id' => $this->user->id,
        'room_id' => $this->room->id,
    ]);

    ReservationCheck::create([
        'reservation_id' => $reservation->id,
        'type' => 'check_in',
        'user_id' => $this->user->id,
        'occurred_at' => now(),
        'verification_status' => CheckVerificationStatus::PENDING,
    ]);

    $this->actingAs($this->user)
        ->patchJson("/api/v1/reservations/{$reservation->id}/cancel")
        ->assertStatus(422);

    expect($reservation->fresh()->status->value)->toBe('approved');
});
