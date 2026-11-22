<?php

use App\Filament\Pages\DefaultRoomRules as DefaultRoomRulesPage;
use App\Http\Middleware\EnsureUserAcceptedPrivacyPolicy;
use App\Http\Middleware\EnsureUserIsActivated;
use App\Http\Middleware\ValidateKeycloakToken;
use App\Models\DefaultRoomRules;
use App\Models\Reservation;
use App\Models\ReservationCheck;
use App\Models\ReservationPolicy;
use App\Models\ReservationPolicyEntry;
use App\Models\Room;
use App\Models\RoomRuleAcceptance;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->withoutMiddleware([ValidateKeycloakToken::class, EnsureUserAcceptedPrivacyPolicy::class, EnsureUserIsActivated::class]);
    Storage::fake('hetzner');
    Mail::fake();

    Carbon::setTestNow(Carbon::parse('2026-11-04 10:00'));

    $this->user = User::factory()->create(['email' => 'rules-'.uniqid().'@example.com', 'keycloak_id' => 'kc-'.uniqid()]);
    $role = Role::create(['name' => 'resident-'.uniqid(), 'keycloak_group_id' => 'kc-'.uniqid()]);
    $this->user->assignRole($role);

    $this->room = Room::create([
        'name' => 'Werkplaats',
        'slug' => 'werkplaats-rules',
        'description' => 'A room',
        'location' => 'Ground floor',
        'status' => 'available',
        'rules' => [
            ['text' => 'Laat de ruimte schoon achter.', 'text_en' => 'Leave the room clean.'],
            ['text' => 'Geen muziek na 22:00.', 'text_en' => null],
        ],
    ]);

    $policy = ReservationPolicy::create(['role_name' => $role->name, 'max_days_in_advance' => 14]);
    $policy->forceFill(['shield_role_id' => $role->id])->save();
    $policy->rooms()->attach($this->room);
    // Thursday 2026-11-05 (0 = Monday).
    ReservationPolicyEntry::create(['reservation_policy_id' => $policy->id, 'day_of_week' => 3, 'start_time' => '09:00', 'end_time' => '17:00']);

    RateLimiter::clear('reservation-writes:'.$this->user->id);
    $this->actingAs($this->user);
});

afterEach(fn () => Carbon::setTestNow());

function bookRoom(Room $room, ?string $hash): TestResponse
{
    return test()->postJson('/api/v1/reservations', [
        'room_id' => $room->id,
        'name' => 'Sessie',
        'start_at' => '2026-11-05 10:00:00',
        'end_at' => '2026-11-05 11:00:00',
        'share_name' => true,
        'rules_accepted_hash' => $hash,
    ]);
}

function checkInWithRules(Reservation $reservation, ?string $hash): TestResponse
{
    return test()->post("/api/v1/reservations/{$reservation->id}/check-in", array_filter([
        'photos' => [UploadedFile::fake()->image('photo.jpg')],
        'rules_accepted_hash' => $hash,
    ]), ['Accept' => 'application/json']);
}

function activeReservation(Room $room, User $user): Reservation
{
    return Reservation::create([
        'name' => 'Sessie',
        'start_at' => now(),
        'end_at' => now()->addHours(2),
        'status' => 'approved',
        'user_id' => $user->id,
        'room_id' => $room->id,
    ]);
}

it('serves each rule separately with the hash', function () {
    DefaultRoomRules::current()->update(['rules' => [['text' => 'Maak foto\'s.', 'text_en' => 'Take photos.']]]);

    $this->getJson("/api/v1/rooms/{$this->room->id}/rules")
        ->assertOk()
        ->assertJsonPath('data.rules_hash', $this->room->rulesHash())
        ->assertJsonCount(1, 'data.default_rules')
        ->assertJsonCount(2, 'data.rules')
        ->assertJsonPath('data.default_rules.0.text_en', 'Take photos.')
        ->assertJsonPath('data.rules.1.text', 'Geen muziek na 22:00.')
        ->assertJsonPath('data.rules.1.text_en', null);
});

it('refuses a booking without accepting the rules', function () {
    bookRoom($this->room, null)
        ->assertUnprocessable()
        ->assertJsonPath('errors.rules_accepted_hash.0', 'room_rules_required');

    expect(Reservation::count())->toBe(0);
});

it('refuses a booking that accepted an older version of the rules', function () {
    $oldHash = $this->room->rulesHash();
    $this->room->update(['rules' => [['text' => 'Nieuwe regel.', 'text_en' => null]]]);

    bookRoom($this->room, $oldHash)
        ->assertUnprocessable()
        ->assertJsonPath('errors.rules_accepted_hash.0', 'room_rules_outdated');

    expect(Reservation::count())->toBe(0);
});

it('treats reordering the rules as a change', function () {
    $oldHash = $this->room->rulesHash();
    $this->room->update(['rules' => array_reverse($this->room->rules)]);

    expect($this->room->rulesHash())->not->toBe($oldHash);
});

it('records the accepted rules when booking', function () {
    $hash = $this->room->rulesHash();

    bookRoom($this->room, $hash)->assertCreated();

    $acceptance = RoomRuleAcceptance::sole();
    expect($acceptance->moment)->toBe(RoomRuleAcceptance::MOMENT_BOOKING)
        ->and($acceptance->user_id)->toBe($this->user->id)
        ->and($acceptance->rules_hash)->toBe($hash)
        ->and($acceptance->rules)->toHaveCount(2);

    // The record keeps what was accepted, even after the rules change.
    $this->room->update(['rules' => [['text' => 'Nieuwe regel.', 'text_en' => null]]]);
    expect($acceptance->fresh()->rules[0]['text'])->toBe('Laat de ruimte schoon achter.');
});

it('refuses a check-in without accepting the rules and stores no photos', function () {
    $reservation = activeReservation($this->room, $this->user);

    checkInWithRules($reservation, null)
        ->assertUnprocessable()
        ->assertJsonPath('errors.rules_accepted_hash.0', 'room_rules_required');

    expect(ReservationCheck::count())->toBe(0);
    Storage::disk('hetzner')->assertDirectoryEmpty('/');
});

it('asks for acceptance again at check-in, separately from booking', function () {
    $reservation = activeReservation($this->room, $this->user);
    RoomRuleAcceptance::create([
        'reservation_id' => $reservation->id,
        'user_id' => $this->user->id,
        'room_id' => $this->room->id,
        'moment' => RoomRuleAcceptance::MOMENT_BOOKING,
        'rules_hash' => $this->room->rulesHash(),
        'rules' => $this->room->ruleList(),
        'accepted_at' => now(),
    ]);

    checkInWithRules($reservation, null)->assertUnprocessable();

    checkInWithRules($reservation, $this->room->rulesHash())->assertCreated();

    expect(RoomRuleAcceptance::where('reservation_id', $reservation->id)->pluck('moment')->sort()->values()->all())
        ->toBe([RoomRuleAcceptance::MOMENT_BOOKING, RoomRuleAcceptance::MOMENT_CHECK_IN]);
});

it('requires accepting the default rules for a room without its own', function () {
    $this->room->update(['rules' => null]);
    DefaultRoomRules::current()->update(['rules' => [['text' => 'Maak foto\'s.', 'text_en' => null]]]);

    $hash = $this->getJson("/api/v1/rooms/{$this->room->id}/rules")
        ->assertOk()
        ->assertJsonPath('data.rules', [])
        ->json('data.rules_hash');

    bookRoom($this->room, null)
        ->assertUnprocessable()
        ->assertJsonPath('errors.rules_accepted_hash.0', 'room_rules_required');

    bookRoom($this->room, $hash)->assertCreated();

    expect(RoomRuleAcceptance::sole()->default_rules[0]['text'])->toBe('Maak foto\'s.')
        ->and(RoomRuleAcceptance::sole()->rules)->toBe([]);
});

it('asks for acceptance again when the default rules change', function () {
    DefaultRoomRules::current()->update(['rules' => [['text' => 'Oud.', 'text_en' => null]]]);
    $oldHash = $this->room->rulesHash();

    DefaultRoomRules::current()->update(['rules' => [['text' => 'Nieuw.', 'text_en' => null]]]);

    bookRoom($this->room, $oldHash)
        ->assertUnprocessable()
        ->assertJsonPath('errors.rules_accepted_hash.0', 'room_rules_outdated');
});

it('lets an authorized admin edit the default rules', function () {
    $admin = User::factory()->create(['email' => 'rules-admin-'.uniqid().'@example.com', 'keycloak_id' => 'kc-'.uniqid()]);
    $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'View:DefaultRoomRules', 'guard_name' => 'web']));
    $this->actingAs($admin);

    Livewire::test(DefaultRoomRulesPage::class)
        ->set('data.rules', [
            'first' => ['text' => 'Maak foto\'s.', 'text_en' => 'Take photos.'],
            'second' => ['text' => 'Ruim op.', 'text_en' => null],
        ])
        ->call('save')
        ->assertHasNoErrors();

    expect(DefaultRoomRules::current()->ruleList())->toBe([
        ['text' => 'Maak foto\'s.', 'text_en' => 'Take photos.'],
        ['text' => 'Ruim op.', 'text_en' => null],
    ]);
});

it('hides the default rules page from users without permission', function () {
    expect(DefaultRoomRulesPage::canAccess())->toBeFalse();
});

it('asks nothing for a room without rules', function (?array $emptyRules) {
    $this->room->update(['rules' => $emptyRules]);

    $this->getJson("/api/v1/rooms/{$this->room->id}/rules")
        ->assertOk()
        ->assertJsonPath('data.rules_hash', null);

    bookRoom($this->room, null)->assertCreated();
    checkInWithRules(activeReservation($this->room, $this->user), null)->assertCreated();

    expect(RoomRuleAcceptance::count())->toBe(0);
})->with([
    'null' => [null],
    'empty list' => [[]],
    'blank rule' => [[['text' => '  ', 'text_en' => null]]],
]);
