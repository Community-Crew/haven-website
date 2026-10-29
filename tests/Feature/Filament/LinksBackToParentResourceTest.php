<?php

use App\Filament\Resources\BoardPositionAssignments\BoardPositionAssignmentResource;
use App\Filament\Resources\BoardPositions\Pages\ListBoardPositions;
use App\Filament\Resources\Memberships\MembershipResource;
use App\Filament\Resources\MemberTypes\Pages\ListMemberTypes;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

function actingAsFullBoardAndMembershipAdmin(): User
{
    $admin = User::factory()->create([
        'email' => 'breadcrumb-admin@example.com',
        'keycloak_id' => 'kc-breadcrumb-admin',
    ]);

    foreach ([
        'ViewAny:Membership', 'ViewAny:MemberType',
        'ViewAny:BoardPositionAssignment', 'ViewAny:BoardPosition',
    ] as $name) {
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
    }

    filament()->setCurrentPanel(filament()->getPanel('admin'));
    test()->actingAs($admin);

    return $admin;
}

it('prepends Memberships to the Member Types breadcrumb trail', function () {
    actingAsFullBoardAndMembershipAdmin();

    $breadcrumbs = Livewire::test(ListMemberTypes::class)->instance()->getBreadcrumbs();

    expect($breadcrumbs)->toHaveKey(MembershipResource::getUrl('index'))
        ->and($breadcrumbs[MembershipResource::getUrl('index')])->toBe('Memberships');
});

it('prepends Board Position Assignments to the Board Positions breadcrumb trail', function () {
    actingAsFullBoardAndMembershipAdmin();

    $breadcrumbs = Livewire::test(ListBoardPositions::class)->instance()->getBreadcrumbs();

    expect($breadcrumbs)->toHaveKey(BoardPositionAssignmentResource::getUrl('index'))
        ->and($breadcrumbs[BoardPositionAssignmentResource::getUrl('index')])->toBe('Board Position Assignments');
});
