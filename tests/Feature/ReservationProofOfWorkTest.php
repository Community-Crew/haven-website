<?php

use App\Filament\Resources\Reservations\Pages\ListReservations;
use App\Http\Middleware\EnsureUserAcceptedPrivacyPolicy;
use App\Http\Middleware\EnsureUserIsActivated;
use App\Http\Middleware\ValidateKeycloakToken;
use App\Models\Reservation;
use App\Models\ReservationPolicy;
use App\Models\ReservationPolicyEntry;
use App\Models\Room;
use App\Models\User;
use App\Services\ProofOfWork;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    // Same exclusions as ReservationRateLimitTest: actingAs() stands in for
    // the Keycloak token check, and the gates are orthogonal to this.
    $this->withoutMiddleware([ValidateKeycloakToken::class, EnsureUserAcceptedPrivacyPolicy::class, EnsureUserIsActivated::class]);
    Http::fake();

    // Low difficulty keeps the PHP solver below fast; the mechanism is the same.
    config(['reservations.proof_of_work.difficulty' => 8, 'reservations.proof_of_work.min_age' => 5, 'reservations.proof_of_work.max_age' => 600]);

    Carbon::setTestNow(Carbon::parse('2026-11-04 10:00'));

    $this->user = User::factory()->create(['email' => 'pow-'.uniqid().'@example.com', 'keycloak_id' => 'kc-'.uniqid()]);
    $role = Role::create(['name' => 'resident-'.uniqid(), 'keycloak_group_id' => 'kc-'.uniqid()]);
    $this->user->assignRole($role);

    $this->room = Room::create(['name' => 'Werkplaats', 'slug' => 'werkplaats-pow', 'description' => 'A room', 'location' => 'Ground floor', 'status' => 'available']);
    $policy = ReservationPolicy::create(['role_name' => $role->name, 'max_days_in_advance' => 14]);
    $policy->forceFill(['shield_role_id' => $role->id])->save();
    $policy->rooms()->attach($this->room);
    // Thursday 2026-11-05 (0 = Monday).
    ReservationPolicyEntry::create(['reservation_policy_id' => $policy->id, 'day_of_week' => 3, 'start_time' => '09:00', 'end_time' => '17:00']);

    RateLimiter::clear('reservation-writes:'.$this->user->id);
    RateLimiter::clear('reservation-challenges:'.$this->user->id);

    $this->actingAs($this->user);
});

afterEach(fn () => Carbon::setTestNow());

function powChallenge(): string
{
    return test()->postJson('/api/v1/reservations/challenge')->assertOk()->json('data.challenge');
}

function solvePow(string $challenge, int $difficulty = 8): int
{
    $pow = app(ProofOfWork::class);

    for ($counter = 0; ; $counter++) {
        if ($pow->leadingZeroBits(hash('sha256', $challenge.':'.$counter, true)) >= $difficulty) {
            return $counter;
        }
    }
}

function reservationPayload(array $pow = []): array
{
    return [
        'room_id' => test()->room->id,
        'name' => 'Spelletjesavond',
        'start_at' => '2026-11-05 10:00:00',
        'end_at' => '2026-11-05 12:00:00',
        'share_name' => false,
        ...$pow,
    ];
}

it('hands out a challenge with its difficulty and timing', function () {
    $this->postJson('/api/v1/reservations/challenge')
        ->assertOk()
        ->assertJsonPath('data.difficulty', 8)
        ->assertJsonPath('data.min_age', 5)
        ->assertJsonPath('data.expires_in', 600);
});

it('accepts a solved challenge, but only once', function () {
    $challenge = powChallenge();
    $nonce = solvePow($challenge);
    $this->travel(6)->seconds();

    $this->postJson('/api/v1/reservations', reservationPayload(['pow_challenge' => $challenge, 'pow_nonce' => $nonce]))
        ->assertCreated();

    $this->postJson('/api/v1/reservations', reservationPayload(['pow_challenge' => $challenge, 'pow_nonce' => $nonce, 'start_at' => '2026-11-05 13:00:00', 'end_at' => '2026-11-05 14:00:00']))
        ->assertUnprocessable()
        ->assertJsonPath('errors.pow_challenge.0', 'proof_of_work_reused');
});

it('refuses a reservation without a solution', function () {
    $this->postJson('/api/v1/reservations', reservationPayload())
        ->assertUnprocessable()
        ->assertJsonPath('errors.pow_challenge.0', 'proof_of_work_missing');
});

it('refuses a solution submitted faster than anyone fills the form in', function () {
    $challenge = powChallenge();

    $this->postJson('/api/v1/reservations', reservationPayload(['pow_challenge' => $challenge, 'pow_nonce' => solvePow($challenge)]))
        ->assertUnprocessable()
        ->assertJsonPath('errors.pow_challenge.0', 'proof_of_work_too_fast');
});

it('refuses an expired, unsolved, tampered or someone else\'s challenge', function (Closure $mutate, string $reason) {
    $challenge = powChallenge();
    $nonce = solvePow($challenge);
    $this->travel(6)->seconds();

    [$challenge, $nonce] = $mutate($challenge, $nonce);

    $this->postJson('/api/v1/reservations', reservationPayload(['pow_challenge' => $challenge, 'pow_nonce' => $nonce]))
        ->assertUnprocessable()
        ->assertJsonPath('errors.pow_challenge.0', $reason);
})->with([
    'expired' => [function ($challenge, $nonce) {
        test()->travel(10)->minutes();

        return [$challenge, $nonce];
    }, 'proof_of_work_expired'],
    'unsolved' => [function ($challenge, $nonce) {
        // A counter that doesn't solve it (the first one that fails).
        $pow = app(ProofOfWork::class);
        $wrong = collect(range(0, 50))->first(fn ($i) => $pow->leadingZeroBits(hash('sha256', $challenge.':'.$i, true)) < 8);

        return [$challenge, $wrong];
    }, 'proof_of_work_unsolved'],
    'tampered difficulty' => [function ($challenge, $nonce) {
        [$payload, $signature] = explode('.', $challenge);
        $data = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
        $data['d'] = 1;

        return [rtrim(strtr(base64_encode(json_encode($data)), '+/', '-_'), '=').'.'.$signature, $nonce];
    }, 'proof_of_work_invalid'],
    'someone else\'s' => [function ($challenge, $nonce) {
        $other = User::factory()->create(['email' => 'other-'.uniqid().'@example.com', 'keycloak_id' => 'kc-'.uniqid()]);
        $theirs = app(ProofOfWork::class)->issue($other)['challenge'];

        return [$theirs, solvePow($theirs)];
    }, 'proof_of_work_invalid'],
]);

it('doesn\'t use up the challenge on a reservation that fails for another reason', function () {
    $challenge = powChallenge();
    $nonce = solvePow($challenge);
    $this->travel(6)->seconds();

    $this->postJson('/api/v1/reservations', reservationPayload(['pow_challenge' => $challenge, 'pow_nonce' => $nonce, 'name' => '']))
        ->assertUnprocessable()
        ->assertJsonMissingPath('errors.pow_challenge');

    $this->postJson('/api/v1/reservations', reservationPayload(['pow_challenge' => $challenge, 'pow_nonce' => $nonce]))
        ->assertCreated();
});

it('limits how many challenges can be fetched, so they can\'t be stockpiled', function () {
    for ($i = 0; $i < 6; $i++) {
        $this->postJson('/api/v1/reservations/challenge')->assertOk();
    }

    $this->postJson('/api/v1/reservations/challenge')->assertStatus(429);
});

it('can be switched off', function () {
    config(['reservations.proof_of_work.difficulty' => 0]);

    $this->postJson('/api/v1/reservations', reservationPayload())->assertCreated();
});

it('counts leading zero bits', function () {
    $pow = app(ProofOfWork::class);

    expect($pow->leadingZeroBits("\xff"))->toBe(0)
        ->and($pow->leadingZeroBits("\x01"))->toBe(7)
        ->and($pow->leadingZeroBits("\x00\x80"))->toBe(8)
        ->and($pow->leadingZeroBits("\x00\x00\x0f"))->toBe(20);
});

it('records how long the challenge was out, without exposing it in the API', function () {
    $challenge = powChallenge();
    $nonce = solvePow($challenge);
    $this->travel(42)->seconds();

    $response = $this->postJson('/api/v1/reservations', reservationPayload(['pow_challenge' => $challenge, 'pow_nonce' => $nonce]))
        ->assertCreated()
        ->assertJsonMissingPath('data.challenge_age_seconds');

    expect(Reservation::find($response->json('data.id'))->challenge_age_seconds)->toBe(42);
});

it('flags a booking made within seconds of opening the page, and one right after the window opened', function () {
    config(['reservations.window_open_time' => '20:00']);

    $fast = Reservation::factory()->make(['challenge_age_seconds' => 6, 'created_at' => Carbon::parse('2026-11-04 14:00:00')]);
    $rush = Reservation::factory()->make(['challenge_age_seconds' => 95, 'created_at' => Carbon::parse('2026-11-04 20:00:12')]);
    $both = Reservation::factory()->make(['challenge_age_seconds' => 5, 'created_at' => Carbon::parse('2026-11-04 20:00:03')]);
    $normal = Reservation::factory()->make(['challenge_age_seconds' => 95, 'created_at' => Carbon::parse('2026-11-04 20:05:00')]);
    $admin = Reservation::factory()->make(['challenge_age_seconds' => null, 'created_at' => Carbon::parse('2026-11-04 14:00:00')]);

    expect($fast->bookingSignals())->toHaveKey('fast')->not->toHaveKey('window_rush')
        ->and($rush->bookingSignals())->toHaveKey('window_rush')->not->toHaveKey('fast')
        ->and($both->bookingSignals())->toHaveKeys(['fast', 'window_rush'])
        ->and($normal->bookingSignals())->toBe([])
        ->and($admin->bookingSignals())->toBe([]);
});

it('lets admins filter reservations on suspiciously fast bookings', function () {
    $admin = User::factory()->create(['email' => 'pow-admin-'.uniqid().'@example.com', 'keycloak_id' => 'kc-'.uniqid()]);
    foreach (['ViewAny:Reservation', 'View:Reservation'] as $permission) {
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));
    }
    filament()->setCurrentPanel(filament()->getPanel('admin'));
    $this->actingAs($admin);

    $base = ['room_id' => $this->room->id, 'user_id' => $this->user->id, 'status' => 'approved', 'name' => 'x'];
    $fast = Reservation::create([...$base, 'start_at' => '2026-11-05 09:00', 'end_at' => '2026-11-05 10:00', 'challenge_age_seconds' => 5]);
    $slow = Reservation::create([...$base, 'start_at' => '2026-11-05 11:00', 'end_at' => '2026-11-05 12:00', 'challenge_age_seconds' => 80]);

    Livewire::test(ListReservations::class)
        ->filterTable('booked_suspiciously_fast')
        ->assertCanSeeTableRecords([$fast])
        ->assertCanNotSeeTableRecords([$slow]);
});
