<?php

use App\Console\Commands\AutoRejectMissedCheckouts;
use App\Enums\CheckType;
use App\Enums\CheckVerificationStatus;
use App\Http\Middleware\EnsureUserAcceptedPrivacyPolicy;
use App\Http\Middleware\EnsureUserIsActivated;
use App\Http\Middleware\ValidateKeycloakToken;
use App\Models\Media;
use App\Models\Reservation;
use App\Models\ReservationCheck;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// Same middleware exclusions as ReservationCheckTest.
beforeEach(function () {
    $this->withoutMiddleware([
        ValidateKeycloakToken::class,
        EnsureUserAcceptedPrivacyPolicy::class,
        EnsureUserIsActivated::class,
    ]);

    Storage::fake('hetzner');

    config(['reservations.checks_live_since' => '2026-10-30 12:00']);
    $this->travelTo(AutoRejectMissedCheckouts::liveSince()->addWeek());

    $this->user = User::factory()->create([
        'email' => 'missed-photos@example.com',
        'keycloak_id' => 'kc-missed-photos',
    ]);

    $this->room = Room::create([
        'name' => 'Werkplaats',
        'slug' => 'werkplaats-missed-photos',
        'description' => 'A room',
        'location' => 'Ground floor',
        'status' => 'available',
    ]);

    $this->makeReservation = fn (array $attributes = []) => Reservation::create([
        'name' => 'Werkplaats sessie',
        'start_at' => now()->subHours(4),
        'end_at' => now()->subHours(2),
        'status' => 'approved',
        'user_id' => $this->user->id,
        'room_id' => $this->room->id,
        ...$attributes,
    ]);

    $this->addCheck = function (Reservation $reservation, CheckType $type, bool $withPhoto = true) {
        $check = ReservationCheck::create([
            'reservation_id' => $reservation->id,
            'type' => $type,
            'user_id' => $reservation->user_id,
            'occurred_at' => now(),
            'verification_status' => $withPhoto ? CheckVerificationStatus::PENDING : CheckVerificationStatus::REJECTED,
        ]);

        if ($withPhoto) {
            Media::createFromUploadedFile(UploadedFile::fake()->image('photo.jpg'), ReservationCheck::PHOTO_COLLECTION)
                ->update(['mediable_type' => $check->getMorphClass(), 'mediable_id' => $check->id]);
        }
    };
});

it('lists a finished reservation with no photos at all', function () {
    $reservation = ($this->makeReservation)();

    $this->actingAs($this->user)
        ->getJson('/api/v1/reservations/me/missed-photos')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $reservation->id)
        ->assertJsonPath('data.0.room_name', 'Werkplaats')
        ->assertJsonPath('data.0.missing', ['check_in', 'check_out']);
});

it('counts an auto-rejected check-out without photos as missing', function () {
    $reservation = ($this->makeReservation)();
    ($this->addCheck)($reservation, CheckType::CHECK_IN);
    ($this->addCheck)($reservation, CheckType::CHECK_OUT, withPhoto: false);

    $this->actingAs($this->user)
        ->getJson('/api/v1/reservations/me/missed-photos')
        ->assertJsonPath('data.0.missing', ['check_out']);
});

it('leaves out reservations with both check-in and check-out photos', function () {
    $reservation = ($this->makeReservation)();
    ($this->addCheck)($reservation, CheckType::CHECK_IN);
    ($this->addCheck)($reservation, CheckType::CHECK_OUT);

    $this->actingAs($this->user)
        ->getJson('/api/v1/reservations/me/missed-photos')
        ->assertJsonCount(0, 'data');
});

it('leaves out reservations that can still be checked out, cancelled ones, and ones from before checks went live', function () {
    ($this->makeReservation)(['start_at' => now()->subHours(2), 'end_at' => now()->subMinutes(30)]);
    ($this->makeReservation)(['status' => 'cancelled']);
    ($this->makeReservation)([
        'start_at' => AutoRejectMissedCheckouts::liveSince()->subDay(),
        'end_at' => AutoRejectMissedCheckouts::liveSince()->subDay()->addHours(2),
    ]);

    $this->actingAs($this->user)
        ->getJson('/api/v1/reservations/me/missed-photos')
        ->assertJsonCount(0, 'data');
});

it('returns nothing while RESERVATION_CHECKS_LIVE_SINCE is unset', function () {
    ($this->makeReservation)();
    config(['reservations.checks_live_since' => null]);

    $this->actingAs($this->user)
        ->getJson('/api/v1/reservations/me/missed-photos')
        ->assertJsonCount(0, 'data');
});

it('only lists the user\'s own reservations', function () {
    $other = User::factory()->create(['email' => 'other@example.com', 'keycloak_id' => 'kc-other']);
    ($this->makeReservation)(['user_id' => $other->id]);

    $this->actingAs($this->user)
        ->getJson('/api/v1/reservations/me/missed-photos')
        ->assertJsonCount(0, 'data');
});

it('stops listing a reservation once acknowledged', function () {
    $acknowledged = ($this->makeReservation)();
    $notShown = ($this->makeReservation)();

    $this->actingAs($this->user)
        ->postJson('/api/v1/reservations/me/missed-photos/acknowledge', ['reservation_ids' => [$acknowledged->id]])
        ->assertOk();

    $this->getJson('/api/v1/reservations/me/missed-photos')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $notShown->id);
});

it('ignores other users\' reservation ids when acknowledging', function () {
    $other = User::factory()->create(['email' => 'other@example.com', 'keycloak_id' => 'kc-other']);
    $theirs = ($this->makeReservation)(['user_id' => $other->id]);

    $this->actingAs($this->user)
        ->postJson('/api/v1/reservations/me/missed-photos/acknowledge', ['reservation_ids' => [$theirs->id]])
        ->assertOk();

    expect($theirs->fresh()->missed_photos_acknowledged_at)->toBeNull();
});
