<?php

use App\Filament\Resources\Reservations\Pages\ListReservations;
use App\Filament\Resources\Users\UserResource;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

function makeReservationForLinkTest(User $resident, Room $room): Reservation
{
    return Reservation::create([
        'name' => 'Werkplaats sessie',
        'start_at' => now(),
        'end_at' => now()->addHour(),
        'status' => 'approved',
        'user_id' => $resident->id,
        'room_id' => $room->id,
    ]);
}

beforeEach(function () {
    $this->resident = User::factory()->create([
        'email' => 'linked-resident@example.com',
        'keycloak_id' => 'kc-linked-resident',
    ]);

    $this->room = Room::create([
        'name' => 'Werkplaats', 'slug' => 'werkplaats-user-link',
        'description' => 'A', 'location' => 'Ground', 'status' => 'available',
    ]);
});

it('links the resident column to their User detail page when the admin can view users', function () {
    $admin = User::factory()->create(['email' => 'link-admin@example.com', 'keycloak_id' => 'kc-link-admin']);
    foreach (['ViewAny:Reservation', 'View:User'] as $name) {
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
    }
    filament()->setCurrentPanel(filament()->getPanel('admin'));
    test()->actingAs($admin);

    makeReservationForLinkTest($this->resident, $this->room);

    Livewire::test(ListReservations::class)
        ->assertSuccessful()
        ->assertSee(UserResource::getUrl('view', ['record' => $this->resident]), false);
});

it('does not link the resident column for an admin who cannot view users', function () {
    $admin = User::factory()->create(['email' => 'link-admin-no-user@example.com', 'keycloak_id' => 'kc-link-admin-no-user']);
    $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'ViewAny:Reservation', 'guard_name' => 'web']));
    filament()->setCurrentPanel(filament()->getPanel('admin'));
    test()->actingAs($admin);

    makeReservationForLinkTest($this->resident, $this->room);

    Livewire::test(ListReservations::class)
        ->assertSuccessful()
        ->assertDontSee(UserResource::getUrl('view', ['record' => $this->resident]), false);
});
