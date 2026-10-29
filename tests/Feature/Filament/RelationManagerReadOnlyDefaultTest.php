<?php

use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\RelationManagers\BoardPositionAssignmentsRelationManager;
use App\Filament\Resources\Users\RelationManagers\MembershipsRelationManager;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

// Filament defaults every relation manager on a resource's View page to
// read-only, which was silently hiding Create/Edit/Delete on the resident
// detail page regardless of the viewer's actual permissions - fixed via
// AdminPanelProvider::readOnlyRelationManagersOnResourceViewPagesByDefault(false).
// This guards against that default creeping back in.
function actingAsResidentManager(array $extraPermissions = []): User
{
    $admin = User::factory()->create([
        'email' => 'resident-manager@example.com',
        'keycloak_id' => 'kc-resident-manager',
    ]);

    foreach ([...$extraPermissions] as $name) {
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
    }

    filament()->setCurrentPanel(filament()->getPanel('admin'));
    test()->actingAs($admin);

    return $admin;
}

it('shows the create action for memberships on a resident\'s View page', function () {
    actingAsResidentManager(['ViewAny:Membership', 'Create:Membership']);

    $resident = User::factory()->create([
        'email' => 'membership-resident@example.com',
        'keycloak_id' => 'kc-membership-resident',
    ]);

    Livewire::test(MembershipsRelationManager::class, [
        'ownerRecord' => $resident,
        'pageClass' => ViewUser::class,
    ])->assertTableActionVisible('create');
});

it('shows the create action for board position assignments on a resident\'s View page', function () {
    actingAsResidentManager(['ViewAny:BoardPositionAssignment', 'Create:BoardPositionAssignment']);

    $resident = User::factory()->create([
        'email' => 'board-resident@example.com',
        'keycloak_id' => 'kc-board-resident',
    ]);

    Livewire::test(BoardPositionAssignmentsRelationManager::class, [
        'ownerRecord' => $resident,
        'pageClass' => ViewUser::class,
    ])->assertTableActionVisible('create');
});
