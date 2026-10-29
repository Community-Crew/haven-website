<?php

use App\Filament\Resources\Rooms\Pages\CreateRoom;
use App\Filament\Resources\Rooms\Pages\EditRoom;
use App\Models\Room;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

it('renders the create room form with the check-in example photo section', function () {
    $admin = User::factory()->create(['email' => 'room-form-admin@example.com', 'keycloak_id' => 'kc-room-form-admin']);
    foreach (['ViewAny:Room', 'Create:Room'] as $name) {
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
    }
    filament()->setCurrentPanel(filament()->getPanel('admin'));
    test()->actingAs($admin);

    Livewire::test(CreateRoom::class)->assertSuccessful();
});

it('renders the edit room form and hydrates an existing example photo', function () {
    $admin = User::factory()->create(['email' => 'room-form-admin2@example.com', 'keycloak_id' => 'kc-room-form-admin2']);
    foreach (['ViewAny:Room', 'View:Room', 'Update:Room'] as $name) {
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
    }
    filament()->setCurrentPanel(filament()->getPanel('admin'));
    test()->actingAs($admin);

    $room = Room::create([
        'name' => 'Werkplaats',
        'slug' => 'werkplaats-form-test',
        'description' => 'A room',
        'location' => 'Ground floor',
        'status' => 'available',
    ]);

    Livewire::test(EditRoom::class, ['record' => $room->getRouteKey()])->assertSuccessful();
});
