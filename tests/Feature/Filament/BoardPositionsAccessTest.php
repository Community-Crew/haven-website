<?php

use App\Filament\Resources\BoardPositionAssignments\Pages\ListBoardPositionAssignments;
use App\Filament\Resources\BoardPositions\BoardPositionResource;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

it('hides Board Positions from the sidebar', function () {
    expect(BoardPositionResource::shouldRegisterNavigation())->toBeFalse();
});

it('links to Board Positions from the Board Position Assignments list for an admin who can view them', function () {
    $admin = User::factory()->create([
        'email' => 'board-assignment-admin@example.com',
        'keycloak_id' => 'kc-board-assignment-admin',
    ]);

    foreach (['ViewAny:BoardPositionAssignment', 'ViewAny:BoardPosition'] as $name) {
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
    }

    filament()->setCurrentPanel(filament()->getPanel('admin'));
    test()->actingAs($admin);

    Livewire::test(ListBoardPositionAssignments::class)
        ->assertActionExists('boardPositions')
        ->assertActionVisible('boardPositions');
});

it('hides the Board Positions button from an admin without that permission', function () {
    $admin = User::factory()->create([
        'email' => 'board-assignment-only-admin@example.com',
        'keycloak_id' => 'kc-board-assignment-only-admin',
    ]);

    $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'ViewAny:BoardPositionAssignment', 'guard_name' => 'web']));

    filament()->setCurrentPanel(filament()->getPanel('admin'));
    test()->actingAs($admin);

    Livewire::test(ListBoardPositionAssignments::class)
        ->assertActionHidden('boardPositions');
});
