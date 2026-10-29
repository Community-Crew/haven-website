<?php

use App\Filament\Resources\Memberships\Pages\ListMemberships;
use App\Filament\Resources\MemberTypes\MemberTypeResource;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

it('hides Member Types from the sidebar', function () {
    expect(MemberTypeResource::shouldRegisterNavigation())->toBeFalse();
});

it('links to Member Types from the Memberships list for an admin who can view them', function () {
    $admin = User::factory()->create([
        'email' => 'membership-admin@example.com',
        'keycloak_id' => 'kc-membership-admin',
    ]);

    foreach (['ViewAny:Membership', 'ViewAny:MemberType'] as $name) {
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
    }

    filament()->setCurrentPanel(filament()->getPanel('admin'));
    test()->actingAs($admin);

    Livewire::test(ListMemberships::class)
        ->assertActionExists('memberTypes')
        ->assertActionVisible('memberTypes');
});

it('hides the Member Types button from an admin without that permission', function () {
    $admin = User::factory()->create([
        'email' => 'membership-only-admin@example.com',
        'keycloak_id' => 'kc-membership-only-admin',
    ]);

    $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'ViewAny:Membership', 'guard_name' => 'web']));

    filament()->setCurrentPanel(filament()->getPanel('admin'));
    test()->actingAs($admin);

    Livewire::test(ListMemberships::class)
        ->assertActionHidden('memberTypes');
});
