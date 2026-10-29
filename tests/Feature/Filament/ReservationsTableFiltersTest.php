<?php

use App\Filament\Resources\Reservations\Pages\ListReservations;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

function actingAsReservationsListAdmin(): User
{
    $admin = User::factory()->create([
        'email' => 'reservations-list-admin@example.com',
        'keycloak_id' => 'kc-reservations-list-admin',
    ]);

    foreach (['ViewAny:Reservation', 'View:Reservation'] as $name) {
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
    }

    filament()->setCurrentPanel(filament()->getPanel('admin'));
    test()->actingAs($admin);

    return $admin;
}

beforeEach(function () {
    actingAsReservationsListAdmin();

    $this->alice = User::factory()->create(['email' => 'alice@example.com', 'keycloak_id' => 'kc-alice']);
    $this->bob = User::factory()->create(['email' => 'bob@example.com', 'keycloak_id' => 'kc-bob']);

    $this->werkplaats = Room::create([
        'name' => 'Werkplaats', 'slug' => 'werkplaats-filters',
        'description' => 'A', 'location' => 'Ground', 'status' => 'available',
    ]);
    $this->tuinkamer = Room::create([
        'name' => 'Tuinkamer', 'slug' => 'tuinkamer-filters',
        'description' => 'A', 'location' => 'Ground', 'status' => 'available',
    ]);

    $this->aliceWerkplaats = Reservation::create([
        'name' => "Alice's session", 'start_at' => now()->addDays(5), 'end_at' => now()->addDays(5)->addHour(),
        'status' => 'approved', 'user_id' => $this->alice->id, 'room_id' => $this->werkplaats->id,
    ]);
    $this->bobTuinkamer = Reservation::create([
        'name' => "Bob's session", 'start_at' => now()->addDays(20), 'end_at' => now()->addDays(20)->addHour(),
        'status' => 'pending', 'user_id' => $this->bob->id, 'room_id' => $this->tuinkamer->id,
    ]);
});

it('filters by status', function () {
    Livewire::test(ListReservations::class)
        ->filterTable('status', 'pending')
        ->assertCanSeeTableRecords([$this->bobTuinkamer])
        ->assertCanNotSeeTableRecords([$this->aliceWerkplaats]);
});

it('filters by room', function () {
    Livewire::test(ListReservations::class)
        ->filterTable('room_id', $this->werkplaats->id)
        ->assertCanSeeTableRecords([$this->aliceWerkplaats])
        ->assertCanNotSeeTableRecords([$this->bobTuinkamer]);
});

it('filters by resident', function () {
    Livewire::test(ListReservations::class)
        ->filterTable('user_id', $this->bob->id)
        ->assertCanSeeTableRecords([$this->bobTuinkamer])
        ->assertCanNotSeeTableRecords([$this->aliceWerkplaats]);
});

it('filters by a start_at date range', function () {
    Livewire::test(ListReservations::class)
        ->filterTable('start_at', [
            'from' => now()->addDays(1)->toDateString(),
            'until' => now()->addDays(10)->toDateString(),
        ])
        ->assertCanSeeTableRecords([$this->aliceWerkplaats])
        ->assertCanNotSeeTableRecords([$this->bobTuinkamer]);
});
