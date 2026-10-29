<?php

use App\Enums\CheckVerificationStatus;
use App\Http\Middleware\EnsureUserAcceptedPrivacyPolicy;
use App\Http\Middleware\EnsureUserIsActivated;
use App\Http\Middleware\ValidateKeycloakToken;
use App\Models\Reservation;
use App\Models\ReservationCheck;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

// ValidateKeycloakToken hits a real Keycloak JWKS endpoint, so it's excluded
// the same way ReservationRateLimitTest does it - actingAs() stands in for
// it. EnsureUserAcceptedPrivacyPolicy/EnsureUserIsActivated are excluded too
// since they're orthogonal to what's under test here.
beforeEach(function () {
    $this->withoutMiddleware([
        ValidateKeycloakToken::class,
        EnsureUserAcceptedPrivacyPolicy::class,
        EnsureUserIsActivated::class,
    ]);

    Storage::fake('hetzner');

    $this->user = User::factory()->create([
        'email' => 'checker@example.com',
        'keycloak_id' => 'kc-checker',
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

// Multipart uploads can't ride along with postJson() (it JSON-encodes the
// body, which can't carry a file), so this hits the plain post() helper but
// forces the Accept header postJson() would normally set - otherwise a
// thrown ValidationException renders as a redirect instead of a 422 JSON
// response.
function checkIn(Reservation $reservation, array $filenames = ['photo.jpg']): TestResponse
{
    return test()->post("/api/v1/reservations/{$reservation->id}/check-in", [
        'photos' => array_map(fn (string $name) => UploadedFile::fake()->image($name), $filenames),
    ], ['Accept' => 'application/json']);
}

function checkOut(Reservation $reservation, array $filenames = ['photo.jpg']): TestResponse
{
    return test()->post("/api/v1/reservations/{$reservation->id}/check-out", [
        'photos' => array_map(fn (string $name) => UploadedFile::fake()->image($name), $filenames),
    ], ['Accept' => 'application/json']);
}

it('checks in with a photo and leaves it pending verification', function () {
    $this->actingAs($this->user);

    $response = checkIn($this->reservation)->assertStatus(201);

    $check = ReservationCheck::sole();
    expect($check->type->value)->toBe('check_in')
        ->and($check->verification_status)->toBe(CheckVerificationStatus::PENDING)
        ->and($check->flag_reason)->toBeNull()
        ->and($check->photo_urls)->toHaveCount(1);

    // Plain strings, not the enum's own jsonSerialize() shape - the
    // resident app matches these against literal 'check_in'/'pending' to
    // decide whether the check-in button should still show.
    expect($response->json('data.type'))->toBe('check_in')
        ->and($response->json('data.verification_status'))->toBe('pending');
});

it('accepts multiple photos for a single check-in', function () {
    $this->actingAs($this->user);

    checkIn($this->reservation, ['front.jpg', 'back.jpg', 'meter.jpg'])->assertStatus(201);

    $check = ReservationCheck::sole();
    expect($check->photos)->toHaveCount(3)
        ->and($check->photo_urls)->toHaveCount(3);
});

it('rejects a check-in from someone other than the reservation owner', function () {
    $other = User::factory()->create([
        'email' => 'someone-else@example.com',
        'keycloak_id' => 'kc-someone-else',
    ]);

    $this->actingAs($other);

    checkIn($this->reservation)->assertStatus(403);
    expect(ReservationCheck::count())->toBe(0);
});

it('refuses to check in a reservation that is not approved', function () {
    $this->reservation->update(['status' => 'pending']);
    $this->actingAs($this->user);

    checkIn($this->reservation)->assertStatus(422);
    expect(ReservationCheck::count())->toBe(0);
});

it('refuses a second check-in on the same reservation', function () {
    $this->actingAs($this->user);

    checkIn($this->reservation, ['first.jpg'])->assertStatus(201);
    checkIn($this->reservation, ['second.jpg'])->assertStatus(422);

    expect(ReservationCheck::count())->toBe(1);
});

it('refuses to check out before checking in', function () {
    $this->actingAs($this->user);

    checkOut($this->reservation)->assertStatus(422);
    expect(ReservationCheck::count())->toBe(0);
});

it('checks out successfully once already checked in', function () {
    $this->actingAs($this->user);

    checkIn($this->reservation)->assertStatus(201);
    checkOut($this->reservation)->assertStatus(201);

    expect(ReservationCheck::where('type', 'check_out')->exists())->toBeTrue();
});

it('flags a check-in far outside the reservation window but still leaves it pending', function () {
    $this->reservation->update([
        'start_at' => now()->subHours(3),
        'end_at' => now()->addHours(1),
    ]);
    $this->actingAs($this->user);

    checkIn($this->reservation)->assertStatus(201);

    $check = ReservationCheck::sole();
    expect($check->flag_reason)->not->toBeNull()
        ->and($check->verification_status)->toBe(CheckVerificationStatus::PENDING);
});

it('never exposes flag_reason through the API response', function () {
    $this->reservation->update([
        'start_at' => now()->subHours(3),
        'end_at' => now()->addHours(1),
    ]);
    $this->actingAs($this->user);

    checkIn($this->reservation)
        ->assertStatus(201)
        ->assertJsonMissingPath('data.flag_reason');
});

it('refuses to check in more than the grace window before the reservation starts', function () {
    $this->reservation->update([
        'start_at' => now()->addMinutes(30),
        'end_at' => now()->addHours(2),
    ]);
    $this->actingAs($this->user);

    checkIn($this->reservation)->assertStatus(422);
    expect(ReservationCheck::count())->toBe(0);
});

it('allows a check-in within the grace window before the reservation starts', function () {
    $this->reservation->update([
        'start_at' => now()->addMinutes(10),
        'end_at' => now()->addHours(2),
    ]);
    $this->actingAs($this->user);

    checkIn($this->reservation)->assertStatus(201);
});

it('refuses to check in once the reservation has already ended', function () {
    $this->reservation->update([
        'start_at' => now()->subHours(3),
        'end_at' => now()->subHour(),
    ]);
    $this->actingAs($this->user);

    checkIn($this->reservation)->assertStatus(422);
    expect(ReservationCheck::count())->toBe(0);
});

it('still allows a check-in right up until the reservation ends', function () {
    $this->reservation->update([
        'start_at' => now()->subHours(3),
        'end_at' => now()->addMinute(),
    ]);
    $this->actingAs($this->user);

    checkIn($this->reservation)->assertStatus(201);
});

it('allows a check-out within the 1 hour grace period after the reservation ends', function () {
    $this->reservation->update([
        'start_at' => now()->subHours(3),
        'end_at' => now()->subMinutes(30),
    ]);
    $this->actingAs($this->user);

    ReservationCheck::create([
        'reservation_id' => $this->reservation->id,
        'type' => 'check_in',
        'user_id' => $this->user->id,
        'occurred_at' => now()->subHours(3),
        'verification_status' => CheckVerificationStatus::VERIFIED,
    ]);

    checkOut($this->reservation)->assertStatus(201);
});

it('refuses to check out once the 1 hour grace period after the reservation ends has passed', function () {
    $this->reservation->update([
        'start_at' => now()->subHours(4),
        'end_at' => now()->subHours(2),
    ]);
    $this->actingAs($this->user);

    ReservationCheck::create([
        'reservation_id' => $this->reservation->id,
        'type' => 'check_in',
        'user_id' => $this->user->id,
        'occurred_at' => now()->subHours(4),
        'verification_status' => CheckVerificationStatus::VERIFIED,
    ]);

    checkOut($this->reservation)->assertStatus(422);
    expect(ReservationCheck::where('type', 'check_out')->count())->toBe(0);
});
