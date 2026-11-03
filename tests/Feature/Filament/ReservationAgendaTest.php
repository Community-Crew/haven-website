<?php

use App\Enums\ReservationStatus;
use App\Filament\Resources\Reservations\Pages\ReservationAgenda;
use App\Filament\Resources\Reservations\ReservationResource;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    // Wednesday 4 November 2026, so the week runs Mon 2 - Sun 8 November.
    Carbon::setTestNow(Carbon::parse('2026-11-04 15:00'));

    $admin = User::factory()->create(['email' => 'agenda-'.uniqid().'@example.com', 'keycloak_id' => 'kc-'.uniqid()]);
    foreach (['ViewAny:Reservation', 'View:Reservation', 'Create:Reservation'] as $permission) {
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));
    }
    filament()->setCurrentPanel(filament()->getPanel('admin'));
    $this->actingAs($admin);

    $this->resident = User::factory()->create(['name' => 'Sanne de Vries', 'email' => 'agenda-res-'.uniqid().'@example.com', 'keycloak_id' => 'kc-'.uniqid()]);
    $this->kitchen = Room::create(['name' => 'Keuken', 'slug' => 'keuken-agenda', 'description' => 'A room', 'location' => 'Ground floor', 'status' => 'available']);
    $this->workshop = Room::create(['name' => 'Werkplaats', 'slug' => 'werkplaats-agenda', 'description' => 'A room', 'location' => 'Ground floor', 'status' => 'available']);
});

afterEach(fn () => Carbon::setTestNow());

function agendaReservation(Room $room, string $start, string $end, array $overrides = []): Reservation
{
    return Reservation::create([
        'name' => 'Spelletjesavond',
        'start_at' => $start,
        'end_at' => $end,
        'status' => ReservationStatus::APPROVED,
        'user_id' => test()->resident->id,
        'room_id' => $room->id,
        ...$overrides,
    ]);
}

/**
 * @return array<int, int> reservation ids per column key
 */
function blocksPerColumn(array $agenda): array
{
    return collect($agenda['columns'])
        ->mapWithKeys(fn ($column) => [$column['key'] => collect($column['blocks'])->pluck('reservation.id')->all()])
        ->all();
}

it('shows this week by default, each reservation in its day', function () {
    $wednesday = agendaReservation($this->kitchen, '2026-11-04 10:00', '2026-11-04 12:00', ['name' => 'Bakken']);
    $sunday = agendaReservation($this->workshop, '2026-11-08 14:00', '2026-11-08 16:00');
    $nextWeek = agendaReservation($this->kitchen, '2026-11-10 10:00', '2026-11-10 11:00');

    $page = Livewire::test(ReservationAgenda::class)
        ->assertSuccessful()
        ->assertSee('Bakken')
        ->assertSee('2 – 8 November 2026');

    $columns = blocksPerColumn($page->instance()->getAgenda());

    expect(array_keys($columns))->toHaveCount(7)
        ->and($columns['2026-11-04'])->toBe([$wednesday->id])
        ->and($columns['2026-11-08'])->toBe([$sunday->id])
        ->and(collect($columns)->flatten())->not->toContain($nextWeek->id);
});

it('positions blocks by time and splits overlapping ones into lanes', function () {
    $first = agendaReservation($this->kitchen, '2026-11-04 10:00', '2026-11-04 12:00');
    $second = agendaReservation($this->workshop, '2026-11-04 11:00', '2026-11-04 13:00');
    $later = agendaReservation($this->kitchen, '2026-11-04 14:00', '2026-11-04 16:00');

    $blocks = collect(Livewire::test(ReservationAgenda::class)->instance()->getAgenda()['columns'])
        ->firstWhere('key', '2026-11-04')['blocks'];
    $byId = collect($blocks)->keyBy('reservation.id');

    // 08:00-24:00 is 960 minutes; 10:00 is 120 in, 2 hours is 120 long.
    expect($byId[$first->id]['top'])->toBe(12.5)
        ->and($byId[$first->id]['height'])->toBe(12.5)
        ->and([$byId[$first->id]['lane'], $byId[$first->id]['lanes']])->toBe([0, 2])
        ->and([$byId[$second->id]['lane'], $byId[$second->id]['lanes']])->toBe([1, 2])
        ->and([$byId[$later->id]['lane'], $byId[$later->id]['lanes']])->toBe([0, 1]);
});

it('widens the hours for reservations outside 08:00-24:00 and splits ones past midnight', function () {
    $early = agendaReservation($this->kitchen, '2026-11-04 06:30', '2026-11-04 07:30');
    $untilMidnight = agendaReservation($this->workshop, '2026-11-04 22:00', '2026-11-05 00:00');

    $agenda = Livewire::test(ReservationAgenda::class)->instance()->getAgenda();
    $columns = blocksPerColumn($agenda);

    expect($agenda['start'])->toBe(6 * 60)
        ->and($columns['2026-11-04'])->toContain($early->id, $untilMidnight->id)
        // Ending exactly at midnight doesn't spill into the next day.
        ->and($columns['2026-11-05'])->toBe([]);

    // Past midnight it continues in the next day's column, from 00:00.
    $overnight = agendaReservation($this->kitchen, '2026-11-06 23:00', '2026-11-07 01:00');
    $agenda = Livewire::test(ReservationAgenda::class)->instance()->getAgenda();
    $columns = blocksPerColumn($agenda);

    expect($agenda['start'])->toBe(0)
        ->and($columns['2026-11-06'])->toContain($overnight->id)
        ->and($columns['2026-11-07'])->toBe([$overnight->id]);
});

it('hides cancelled and rejected reservations unless asked', function () {
    $cancelled = agendaReservation($this->kitchen, '2026-11-04 10:00', '2026-11-04 12:00', ['status' => ReservationStatus::CANCELLED]);
    $pending = agendaReservation($this->kitchen, '2026-11-04 14:00', '2026-11-04 15:00', ['status' => ReservationStatus::PENDING]);

    $page = Livewire::test(ReservationAgenda::class);
    expect(collect(blocksPerColumn($page->instance()->getAgenda()))->flatten()->all())->toBe([$pending->id]);

    $page->set('showCancelled', true);
    expect(collect(blocksPerColumn($page->instance()->getAgenda()))->flatten()->sort()->values()->all())->toBe([$cancelled->id, $pending->id]);
});

it('filters the week on one room', function () {
    $kitchen = agendaReservation($this->kitchen, '2026-11-04 10:00', '2026-11-04 12:00');
    $workshop = agendaReservation($this->workshop, '2026-11-04 10:00', '2026-11-04 12:00');

    $ids = collect(blocksPerColumn(Livewire::test(ReservationAgenda::class)->set('room', $this->workshop->id)->instance()->getAgenda()))->flatten()->all();

    expect($ids)->toBe([$workshop->id])->not->toContain($kitchen->id);
});

it('shows one day with the rooms side by side, and navigates', function () {
    $kitchen = agendaReservation($this->kitchen, '2026-11-04 10:00', '2026-11-04 12:00');
    $workshop = agendaReservation($this->workshop, '2026-11-04 10:00', '2026-11-04 12:00');
    $thursday = agendaReservation($this->kitchen, '2026-11-05 10:00', '2026-11-05 12:00');

    $page = Livewire::test(ReservationAgenda::class)
        ->call('showDay', '2026-11-04')
        ->assertSet('mode', 'day')
        ->assertSee('Wednesday 4 November 2026');

    expect(blocksPerColumn($page->instance()->getAgenda()))->toBe([
        'room-'.$this->kitchen->id => [$kitchen->id],
        'room-'.$this->workshop->id => [$workshop->id],
    ]);

    $page->call('next')->assertSet('date', '2026-11-05');
    expect(blocksPerColumn($page->instance()->getAgenda())['room-'.$this->kitchen->id])->toBe([$thursday->id]);

    $page->set('mode', 'week')->call('previous')->assertSet('date', '2026-10-29')
        ->call('today')->assertSet('date', '2026-11-04');
});

it('makes the agenda the reservations landing page, with the table one click away', function () {
    $reservation = agendaReservation($this->kitchen, '2026-11-04 10:00', '2026-11-04 12:00', ['name' => 'Bakken']);
    $base = 'http://'.config('services.admin_domain');

    $this->get($base.'/reservations')->assertOk()->assertSee('Bakken')->assertSee(ReservationResource::getUrl('list'), false);
    $this->get($base.'/reservations/list')->assertOk()->assertSee('Bakken')->assertSee('Agenda');
    $this->get($base.'/reservations/'.$reservation->id)->assertOk();
});
