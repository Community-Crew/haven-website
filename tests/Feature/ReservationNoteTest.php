<?php

use App\Http\Middleware\EnsureUserAcceptedPrivacyPolicy;
use App\Http\Middleware\EnsureUserIsActivated;
use App\Http\Middleware\ValidateKeycloakToken;
use App\Models\Reservation;
use App\Models\ReservationNote;
use App\Models\Room;
use App\Models\User;

beforeEach(function () {
    $this->withoutMiddleware([
        ValidateKeycloakToken::class,
        EnsureUserAcceptedPrivacyPolicy::class,
        EnsureUserIsActivated::class,
    ]);

    $this->user = User::factory()->create([
        'email' => 'noted-resident@example.com',
        'keycloak_id' => 'kc-noted-resident',
    ]);

    $this->admin = User::factory()->create([
        'email' => 'note-author@example.com',
        'keycloak_id' => 'kc-note-author',
    ]);

    $this->room = Room::create([
        'name' => 'Werkplaats',
        'slug' => 'werkplaats',
        'description' => 'A room',
        'location' => 'Ground floor',
        'status' => 'available',
    ]);

    $this->reservation = Reservation::create([
        'name' => 'Werkplaats sessie',
        'start_at' => now(),
        'end_at' => now()->addHours(2),
        'status' => 'approved',
        'user_id' => $this->user->id,
        'room_id' => $this->room->id,
    ]);
});

it('scopes visibleToResidentNotes to only notes flipped visible', function () {
    ReservationNote::create([
        'reservation_id' => $this->reservation->id,
        'user_id' => $this->admin->id,
        'body' => 'Internal only - called about a broken lock.',
        'visible_to_resident' => false,
    ]);

    $visible = ReservationNote::create([
        'reservation_id' => $this->reservation->id,
        'user_id' => $this->admin->id,
        'body' => 'Room will be repainted next week, plan accordingly.',
        'visible_to_resident' => true,
    ]);

    $notes = $this->reservation->visibleToResidentNotes;

    expect($notes)->toHaveCount(1)
        ->and($notes->first()->id)->toBe($visible->id);
    expect($this->reservation->notes)->toHaveCount(2);
});

it('exposes only visible_to_resident notes through the resident API, never the internal ones', function () {
    ReservationNote::create([
        'reservation_id' => $this->reservation->id,
        'user_id' => $this->admin->id,
        'body' => 'Internal note - should never reach the app.',
        'visible_to_resident' => false,
    ]);

    ReservationNote::create([
        'reservation_id' => $this->reservation->id,
        'user_id' => $this->admin->id,
        'body' => 'Heads up: the room will be repainted next week.',
        'visible_to_resident' => true,
    ]);

    $this->actingAs($this->user);

    $response = $this->getJson('/api/v1/reservations/me');

    $response->assertStatus(200);
    $notes = $response->json('data.0.notes');

    expect($notes)->toHaveCount(1)
        ->and($notes[0]['body'])->toBe('Heads up: the room will be repainted next week.')
        ->and(collect($notes)->pluck('body')->implode(' '))->not->toContain('should never reach the app');
});
