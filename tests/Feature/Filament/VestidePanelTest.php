<?php

use App\Enums\CheckType;
use App\Enums\CheckVerificationStatus;
use App\Enums\CleanlinessOutcome;
use App\Enums\RoomStatus;
use App\Filament\Vestide\Pages\CleanlinessTimeline;
use App\Filament\Vestide\Pages\ImportMutaties;
use App\Filament\Vestide\Pages\Reservations;
use App\Filament\Vestide\Pages\ResidentRecords;
use App\Filament\Vestide\Pages\RoomStatuses;
use App\Filament\Vestide\Pages\UnitCodes;
use App\Mail\ReservationCheckRejectedMail;
use App\Models\Reservation;
use App\Models\ReservationCheck;
use App\Models\Room;
use App\Models\SentEmail;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Http::fake(fn (Request $request) => str_contains($request->url(), '/protocol/openid-connect/token')
        ? Http::response(['access_token' => 'fake-admin-token'])
        : Http::response(null, 204));
    Storage::fake('hetzner');

    $this->room = Room::create(['name' => 'Living Room', 'slug' => 'living-room-vestide', 'description' => 'A room', 'location' => 'Ground floor', 'status' => 'available']);
    $this->unit = Unit::create(['building' => 'Pollux', 'floor' => '7', 'unit' => '9', 'subunit' => 'D', 'max_residents' => 1]);
});

function vestideUser(string $role = User::VESTIDE_ROLE): User
{
    $user = User::factory()->create(['email' => 'vestide-'.uniqid().'@example.com', 'keycloak_id' => 'kc-vestide-'.uniqid()]);
    // keycloak_group_id pre-set so RoleObserver::created() skips its own
    // Keycloak provisioning call.
    $user->assignRole(Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'], ['keycloak_group_id' => 'kc-group-'.uniqid()]));

    return $user;
}

function actingAsVestide(): User
{
    $user = vestideUser();

    filament()->setCurrentPanel(filament()->getPanel('vestide'));
    test()->actingAs($user);

    return $user;
}

function makeCheckOut(Room $room, User $resident, CheckVerificationStatus $status, ?User $reviewer, array $overrides = []): ReservationCheck
{
    $reservation = Reservation::create([
        'name' => 'Borrel',
        'start_at' => now()->subHours(4),
        'end_at' => now()->subHours(2),
        'status' => 'approved',
        'user_id' => $resident->id,
        'room_id' => $room->id,
    ]);

    return ReservationCheck::create(array_merge([
        'reservation_id' => $reservation->id,
        'type' => CheckType::CHECK_OUT,
        'user_id' => $resident->id,
        'occurred_at' => now()->subHours(2),
        'verification_status' => $status,
        'reviewed_by' => $reviewer?->id,
        'reviewed_at' => $status === CheckVerificationStatus::PENDING ? null : now(),
        'rejection_note' => $status === CheckVerificationStatus::REJECTED ? 'Dishes left in the sink.' : null,
    ], $overrides));
}

function makeResident(string $name, ?Unit $unit = null): User
{
    return User::factory()->create([
        'name' => $name,
        'email' => str($name)->slug().'-'.uniqid().'@example.com',
        'keycloak_id' => 'kc-'.uniqid(),
        'unit_id' => $unit?->id,
        'activated_at' => $unit ? now()->subYear() : null,
    ]);
}

it('lets only Vestide staff and admins into the Vestide panel, and keeps Vestide staff out of the admin panel', function () {
    $vestide = filament()->getPanel('vestide');
    $admin = filament()->getPanel('admin');

    $staff = vestideUser();
    $superAdmin = vestideUser(config('filament-shield.super_admin.name'));
    $resident = vestideUser('Resident');

    expect($staff->canAccessPanel($vestide))->toBeTrue()
        ->and($superAdmin->canAccessPanel($vestide))->toBeTrue()
        ->and($resident->canAccessPanel($vestide))->toBeFalse()
        ->and($staff->canAccessPanel($admin))->toBeFalse()
        ->and($resident->canAccessPanel($admin))->toBeTrue();
});

it('derives clean, dirty and ghosted from reviewed check-outs', function () {
    $reviewer = makeResident('Board member');
    $resident = makeResident('Sanne');

    expect(CleanlinessOutcome::forCheck(makeCheckOut($this->room, $resident, CheckVerificationStatus::VERIFIED, $reviewer)))->toBe(CleanlinessOutcome::CLEAN)
        ->and(CleanlinessOutcome::forCheck(makeCheckOut($this->room, $resident, CheckVerificationStatus::REJECTED, $reviewer)))->toBe(CleanlinessOutcome::DIRTY)
        ->and(CleanlinessOutcome::forCheck(makeCheckOut($this->room, $resident, CheckVerificationStatus::REJECTED, null)))->toBe(CleanlinessOutcome::GHOSTED)
        ->and(CleanlinessOutcome::forCheck(makeCheckOut($this->room, $resident, CheckVerificationStatus::PENDING, null)))->toBeNull();
});

it('shows reviewed check-outs on the timeline, but not pending ones or check-ins', function () {
    actingAsVestide();
    $reviewer = makeResident('Board member');

    $clean = makeCheckOut($this->room, makeResident('Clean Carla'), CheckVerificationStatus::VERIFIED, $reviewer);
    $dirty = makeCheckOut($this->room, makeResident('Dirty Dirk'), CheckVerificationStatus::REJECTED, $reviewer);
    $ghosted = makeCheckOut($this->room, makeResident('Ghost Gijs'), CheckVerificationStatus::REJECTED, null);
    $pending = makeCheckOut($this->room, makeResident('Pending Piet'), CheckVerificationStatus::PENDING, null);
    $checkIn = makeCheckOut($this->room, makeResident('Checkin Cor'), CheckVerificationStatus::REJECTED, $reviewer, ['type' => CheckType::CHECK_IN]);

    Livewire::test(CleanlinessTimeline::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$clean, $dirty, $ghosted])
        ->assertCanNotSeeTableRecords([$pending, $checkIn])
        ->assertSee('Dishes left in the sink.')
        ->assertSee('Er zijn geen uitcheckfoto')
        // The list never names residents - only the details do.
        ->assertDontSee('Dirty Dirk')
        ->assertDontSee('Clean Carla')
        ->filterTable('outcome', CleanlinessOutcome::DIRTY->value)
        ->assertCanSeeTableRecords([$dirty])
        ->assertCanNotSeeTableRecords([$clean, $ghosted])
        ->mountTableAction('details', $dirty);

    $details = view('filament.vestide.reservation-details', [
        'reservation' => $dirty->reservation->load('checks.photos', 'user.unit'),
    ])->render();

    expect($details)->toContain('Voor (incheck)')
        ->toContain('Na (uitcheck)')
        ->toContain('Dirty Dirk')
        ->toContain('Dishes left in the sink.');
});

it('only shows a resident\'s tally once they are searched for by name', function () {
    actingAsVestide();
    $reviewer = makeResident('Board member');
    $dirk = makeResident('Dirty Dirk');
    $carla = makeResident('Clean Carla');

    makeCheckOut($this->room, $dirk, CheckVerificationStatus::REJECTED, $reviewer);
    makeCheckOut($this->room, $dirk, CheckVerificationStatus::REJECTED, $reviewer);
    makeCheckOut($this->room, $dirk, CheckVerificationStatus::REJECTED, null);
    makeCheckOut($this->room, $dirk, CheckVerificationStatus::VERIFIED, $reviewer);
    makeCheckOut($this->room, $carla, CheckVerificationStatus::VERIFIED, $reviewer);

    $page = Livewire::test(ResidentRecords::class)
        ->assertSuccessful()
        ->assertCanNotSeeTableRecords([$dirk, $carla])
        ->assertSee('Zoek een bewoner op naam')
        ->searchTable('Di')
        ->assertCanNotSeeTableRecords([$dirk, $carla])
        ->searchTable('dirk')
        ->assertCanSeeTableRecords([$dirk])
        ->assertCanNotSeeTableRecords([$carla]);

    $row = $page->instance()->getTableRecords()->firstWhere('id', $dirk->id);
    expect($row->clean_count)->toBe(1)
        ->and($row->dirty_count)->toBe(2)
        ->and($row->ghosted_count)->toBe(1);
});

it('issues a code for a unit and moves its current resident out the normal way', function () {
    actingAsVestide();
    $resident = makeResident('Sanne de Vries', $this->unit);

    Livewire::test(UnitCodes::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$this->unit])
        // How many live there, not who.
        ->assertSee('1 bewoner')
        ->assertDontSee('Sanne de Vries')
        ->assertDontSee('printed')
        ->searchTable('Pollux 0709D')
        ->assertCanSeeTableRecords([$this->unit])
        ->callTableAction('issueCode', $this->unit)
        ->assertHasNoTableActionErrors()
        ->assertNotified();

    $resident->refresh();
    expect($resident->moved_out_at)->not->toBeNull()
        ->and($resident->unit_id)->toBeNull()
        ->and($this->unit->registrationCodes()->where('is_used', false)->count())->toBe(1)
        ->and(SentEmail::where('to', $resident->email)->pluck('mailable')->all())->toContain('App\Mail\ResidentMovedOutMail');
});

it('offers the mutaties import inside the Vestide panel', function () {
    actingAsVestide();

    Livewire::test(ImportMutaties::class)
        ->assertSuccessful()
        ->assertActionHidden('downloadLabels');
});

it('tells residents a rejected check-out is shared with Vestide, but not a rejected check-in', function () {
    $resident = makeResident('Sanne');
    $reviewer = makeResident('Board member');

    $checkOut = makeCheckOut($this->room, $resident, CheckVerificationStatus::REJECTED, $reviewer);
    $checkIn = makeCheckOut($this->room, $resident, CheckVerificationStatus::REJECTED, $reviewer, ['type' => CheckType::CHECK_IN]);

    expect((new ReservationCheckRejectedMail($checkOut))->render())->toContain('shared with Vestide')
        ->and((new ReservationCheckRejectedMail($checkIn))->render())->not->toContain('shared with Vestide');
});

it('serves every Vestide page to Vestide staff and nobody else', function () {
    $staff = vestideUser();
    $resident = vestideUser('Resident');
    $base = 'http://'.config('services.admin_domain').'/vestide';

    // Real data, so pages render in Dutch with rows - Dutch day names once
    // broke the timeline's day grouping, which Livewire::test (no panel
    // middleware, so English) didn't catch.
    $this->travelTo('2026-09-27 20:00');
    makeCheckOut($this->room, makeResident('Dirty Dirk', $this->unit), CheckVerificationStatus::REJECTED, makeResident('Board member'));

    foreach (['', '/room-statuses', '/reservations', '/resident-records', '/unit-codes', '/import-mutaties'] as $path) {
        $this->actingAs($staff)->get($base.$path)->assertOk()->assertSee('Tijdlijn')->assertSee('Uitloggen');
        if ($path === '') {
            $this->actingAs($staff)->get($base)->assertSee('zondag 27 september 2026')->assertDontSee('Dirty Dirk');
        }
        $this->actingAs($resident)->get($base.$path)->assertForbidden();
    }
});

it('sends Vestide staff who open the admin panel to their own panel', function () {
    $this->actingAs(vestideUser())
        ->get('http://'.config('services.admin_domain').'/')
        ->assertRedirect(filament()->getPanel('vestide')->getUrl());
});

it('lists reservations without names, and names the resident only in the details', function () {
    actingAsVestide();
    $dirty = makeCheckOut($this->room, makeResident('Dirty Dirk', $this->unit), CheckVerificationStatus::REJECTED, makeResident('Board member'));
    $other = makeCheckOut($this->room, makeResident('Clean Carla'), CheckVerificationStatus::VERIFIED, makeResident('Another board member'));

    Livewire::test(Reservations::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$dirty->reservation, $other->reservation])
        ->assertDontSee('Dirty Dirk')
        ->assertDontSee('Clean Carla')
        ->filterTable('outcome', CleanlinessOutcome::DIRTY->value)
        ->assertCanSeeTableRecords([$dirty->reservation])
        ->assertCanNotSeeTableRecords([$other->reservation])
        ->mountTableAction('details', $dirty->reservation);

    $details = view('filament.vestide.reservation-details', ['reservation' => $dirty->reservation->load('checks.photos', 'user.unit')])->render();

    expect($details)->toContain('Dirty Dirk')
        ->toContain('Pollux 0709D')
        ->toContain('Dishes left in the sink.')
        ->toContain('Voor (incheck)');
});

it('shows one resident\'s reservations when opened from the resident lookup', function () {
    actingAsVestide();
    $dirk = makeResident('Dirty Dirk');
    $mine = makeCheckOut($this->room, $dirk, CheckVerificationStatus::VERIFIED, null);
    $theirs = makeCheckOut($this->room, makeResident('Clean Carla'), CheckVerificationStatus::VERIFIED, null);

    $link = Reservations::forResident($dirk);
    parse_str(parse_url($link, PHP_URL_QUERY), $query);

    // A guessed plain id doesn't work - that would let anyone page through residents.
    Livewire::withQueryParams(['bewoner' => (string) $dirk->id])
        ->test(Reservations::class)
        ->assertDontSee('Dirty Dirk')
        ->assertCanNotSeeTableRecords([$mine->reservation, $theirs->reservation]);

    Livewire::withQueryParams($query)
        ->test(Reservations::class)
        ->assertSee('Reserveringen van Dirty Dirk')
        ->assertCanSeeTableRecords([$mine->reservation])
        ->assertCanNotSeeTableRecords([$theirs->reservation])
        ->callAction('allReservations')
        ->assertCanSeeTableRecords([$mine->reservation, $theirs->reservation]);
});

it('derives each room\'s state from how its last finished reservation was left', function () {
    actingAsVestide();
    $reviewer = makeResident('Board member');
    $dirk = makeResident('Dirty Dirk');

    $kitchen = Room::create(['name' => 'Keuken', 'slug' => 'keuken-vestide', 'description' => 'A room', 'location' => 'Ground floor', 'status' => 'available']);
    $unused = Room::create(['name' => 'Zolder', 'slug' => 'zolder-vestide', 'description' => 'A room', 'location' => 'Top floor', 'status' => 'maintenance']);

    // Living Room: dirty a week ago, clean since.
    makeCheckOut($this->room, $dirk, CheckVerificationStatus::REJECTED, $reviewer)->reservation->update(['start_at' => now()->subDays(7)->subHours(2), 'end_at' => now()->subDays(7)]);
    makeCheckOut($this->room, $dirk, CheckVerificationStatus::VERIFIED, $reviewer);
    // Keuken: last left dirty.
    makeCheckOut($kitchen, $dirk, CheckVerificationStatus::REJECTED, $reviewer);

    $rooms = Livewire::test(RoomStatuses::class)
        ->assertSuccessful()
        ->assertSee('Schoon')
        ->assertSee('Vies achtergelaten')
        ->assertSee('Nog niet gebruikt')
        ->assertSee('Buiten gebruik')
        ->assertDontSee('Dirty Dirk')
        ->instance()
        ->getRooms()
        ->keyBy(fn (array $entry) => $entry['room']->name);

    expect($rooms['Living Room']['label'])->toBe('Schoon')
        ->and($rooms['Living Room']['history'])->toHaveCount(2)
        ->and($rooms['Keuken']['label'])->toBe('Vies achtergelaten')
        ->and($rooms['Zolder']['label'])->toBe('Nog niet gebruikt');

    Livewire::test(RoomStatuses::class)->mountAction('history', ['room' => $kitchen->id])->assertSuccessful();

    $history = view('filament.vestide.room-history', ['room' => $kitchen, 'history' => $rooms['Keuken']['history']])->render();
    expect($history)->toContain('Vies achtergelaten')
        ->toContain('Dishes left in the sink.')
        ->not->toContain('Dirty Dirk');
});

it('shows a room as awaiting review while its last check-out is pending, with the state before it', function () {
    actingAsVestide();
    $reviewer = makeResident('Board member');
    $resident = makeResident('Sanne');

    makeCheckOut($this->room, $resident, CheckVerificationStatus::VERIFIED, $reviewer)->reservation->update(['start_at' => now()->subDays(2)->subHours(2), 'end_at' => now()->subDays(2)]);
    makeCheckOut($this->room, $resident, CheckVerificationStatus::PENDING, null);

    $room = Livewire::test(RoomStatuses::class)->instance()->getRooms()->first();

    expect($room['label'])->toBe('Wacht op beoordeling')
        ->and($room['detail'])->toContain('Daarvoor: schoon');
});

it('lets Vestide mark a room as being cleaned and then cleaned, which overrides a dirty last reservation', function () {
    $staff = actingAsVestide();
    makeCheckOut($this->room, makeResident('Dirty Dirk'), CheckVerificationStatus::REJECTED, makeResident('Board member'));

    $state = fn () => Livewire::test(RoomStatuses::class)->instance()->getRooms()->first()['label'];
    expect($state())->toBe('Vies achtergelaten');

    Livewire::test(RoomStatuses::class)
        ->callAction('startCleaning', arguments: ['room' => $this->room->id])
        ->assertNotified();
    expect($this->room->fresh()->status)->toBe(RoomStatus::CLEANING)
        ->and($state())->toBe('Wordt schoongemaakt');

    Livewire::test(RoomStatuses::class)
        ->callAction('finishCleaning', arguments: ['room' => $this->room->id])
        ->assertNotified();
    $room = $this->room->fresh();
    expect($room->status)->toBe(RoomStatus::AVAILABLE)
        ->and($room->cleaned_at)->not->toBeNull()
        ->and($room->cleaned_by)->toBe($staff->id)
        ->and($state())->toBe('Schoon');

    // The next reservation that ends decides again.
    $this->travel(1)->hours();
    makeCheckOut($this->room, makeResident('Dirty Dirk again'), CheckVerificationStatus::REJECTED, makeResident('Board member'), [])
        ->reservation->update(['start_at' => now()->subMinutes(30), 'end_at' => now()->subMinute()]);
    expect($state())->toBe('Vies achtergelaten');
});

it('keeps an out-of-order room out of order when it is marked cleaned', function () {
    actingAsVestide();
    $this->room->update(['status' => RoomStatus::MAINTENANCE]);

    Livewire::test(RoomStatuses::class)->callAction('finishCleaning', arguments: ['room' => $this->room->id]);

    expect($this->room->fresh()->status)->toBe(RoomStatus::MAINTENANCE)
        ->and($this->room->fresh()->cleaned_at)->not->toBeNull();
});
