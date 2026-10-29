<?php

use App\Http\Middleware\EnsureUserAcceptedPrivacyPolicy;
use App\Http\Middleware\EnsureUserIsActivated;
use App\Http\Middleware\ValidateKeycloakToken;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;

// The resident-facing app's "past" tab sends only `end_at` (deliberately no
// `start_at`, since a past reservation's start is, by definition, before
// today) - Reservation::scopeInDateRange used to force a >= today floor
// regardless, making past reservations impossible to ever see.
beforeEach(function () {
    $this->withoutMiddleware([
        ValidateKeycloakToken::class,
        EnsureUserAcceptedPrivacyPolicy::class,
        EnsureUserIsActivated::class,
    ]);

    $this->user = User::factory()->create([
        'email' => 'date-range-resident@example.com',
        'keycloak_id' => 'kc-date-range-resident',
    ]);

    $this->room = Room::create([
        'name' => 'Werkplaats', 'slug' => 'werkplaats-date-range',
        'description' => 'A', 'location' => 'Ground', 'status' => 'available',
    ]);

    $this->pastReservation = Reservation::create([
        'name' => 'Last week', 'start_at' => now()->subWeek(), 'end_at' => now()->subWeek()->addHour(),
        'status' => 'approved', 'user_id' => $this->user->id, 'room_id' => $this->room->id,
    ]);

    $this->upcomingReservation = Reservation::create([
        'name' => 'Next week', 'start_at' => now()->addWeek(), 'end_at' => now()->addWeek()->addHour(),
        'status' => 'approved', 'user_id' => $this->user->id, 'room_id' => $this->room->id,
    ]);
});

it('excludes past reservations by default', function () {
    $this->actingAs($this->user);

    $ids = $this->getJson('/api/v1/reservations/me')->json('data.*.id');

    expect($ids)->toContain($this->upcomingReservation->id)
        ->not->toContain($this->pastReservation->id);
});

it('returns only past reservations when just end_at is given', function () {
    $this->actingAs($this->user);

    $ids = $this->getJson('/api/v1/reservations/me?end_at='.now()->toDateString())->json('data.*.id');

    expect($ids)->toContain($this->pastReservation->id)
        ->not->toContain($this->upcomingReservation->id);
});

it('still returns upcoming reservations when start_at is given', function () {
    $this->actingAs($this->user);

    $ids = $this->getJson('/api/v1/reservations/me?start_at='.now()->toDateString())->json('data.*.id');

    expect($ids)->toContain($this->upcomingReservation->id)
        ->not->toContain($this->pastReservation->id);
});
