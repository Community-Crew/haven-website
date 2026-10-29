<?php

use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\RelationManagers\ReservationsRelationManager;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

it('lets an admin see a resident\'s reservations from their profile page', function () {
    $admin = User::factory()->create([
        'email' => 'resident-view-admin@example.com',
        'keycloak_id' => 'kc-resident-view-admin',
    ]);

    foreach (['View:User', 'ViewAny:Reservation', 'View:Reservation'] as $name) {
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
    }

    filament()->setCurrentPanel(filament()->getPanel('admin'));
    test()->actingAs($admin);

    $resident = User::factory()->create([
        'email' => 'resident-with-reservations@example.com',
        'keycloak_id' => 'kc-resident-with-reservations',
    ]);

    $room = Room::create([
        'name' => 'Werkplaats',
        'slug' => 'werkplaats-profile',
        'description' => 'A room',
        'location' => 'Ground floor',
        'status' => 'available',
    ]);

    Reservation::create([
        'name' => 'Werkplaats sessie',
        'start_at' => now(),
        'end_at' => now()->addHours(2),
        'status' => 'approved',
        'user_id' => $resident->id,
        'room_id' => $room->id,
    ]);

    Livewire::test(ReservationsRelationManager::class, [
        'ownerRecord' => $resident,
        'pageClass' => ViewUser::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords($resident->reservations);
});
