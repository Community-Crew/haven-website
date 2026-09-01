<?php

use App\Http\Middleware\EnsureUserAcceptedPrivacyPolicy;
use App\Http\Middleware\EnsureUserIsActivated;
use App\Http\Middleware\ValidateKeycloakToken;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

// ValidateKeycloakToken hits a real Keycloak JWKS endpoint, so it's excluded
// here the same way RoomReservationIndexTest does it - actingAs() stands in
// for it. EnsureUserAcceptedPrivacyPolicy/EnsureUserIsActivated are excluded
// too since they're orthogonal to the thing under test (rate limiting), not
// because the limiter depends on skipping them.
beforeEach(function () {
    $this->withoutMiddleware([
        ValidateKeycloakToken::class,
        EnsureUserAcceptedPrivacyPolicy::class,
        EnsureUserIsActivated::class,
    ]);

    $this->user = User::factory()->create([
        'email' => 'rate-limit-tester@example.com',
        'keycloak_id' => 'kc-rate-limit-tester',
    ]);

    RateLimiter::clear('reservation-writes:'.$this->user->id);
});

it('blocks reservation writes after 5 attempts in a minute, regardless of validation outcome', function () {
    $this->actingAs($this->user);

    // Deliberately invalid payload (missing fields) - the limiter counts
    // every request that reaches the route, not just successful ones.
    for ($i = 1; $i <= 5; $i++) {
        $response = $this->postJson('/api/v1/reservations', []);
        $response->assertStatus(422);
    }

    $response = $this->postJson('/api/v1/reservations', []);

    $response->assertStatus(429)
        ->assertJson(['message' => 'Too many reservation attempts. Please wait a minute and try again.']);
});

it('rate limits reservation writes per user, not globally', function () {
    $otherUser = User::factory()->create([
        'email' => 'rate-limit-tester-2@example.com',
        'keycloak_id' => 'kc-rate-limit-tester-2',
    ]);

    $this->actingAs($this->user);
    for ($i = 1; $i <= 5; $i++) {
        $this->postJson('/api/v1/reservations', []);
    }
    $this->postJson('/api/v1/reservations', [])->assertStatus(429);

    $this->actingAs($otherUser);
    $this->postJson('/api/v1/reservations', [])->assertStatus(422);
});
