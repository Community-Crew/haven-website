<?php

use App\Models\BoardPosition;
use App\Models\BoardPositionSignature;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    // The user/roles below all get real keycloak ids, so
    // UserRoleSyncService would attempt actual Keycloak calls on
    // assignRole() - fake it, this test only cares about permission
    // resolution.
    Http::fake();

    $this->user = User::factory()->create([
        'email' => 'nda-gate-tester-'.uniqid().'@example.com',
        'keycloak_id' => 'kc-'.uniqid(),
    ]);

    // keycloak_group_id pre-set so RoleObserver::created()'s guard skips
    // its own (real) Keycloak provisioning call for these test roles.
    $this->gatedRole = Role::create(['name' => 'gated-role-'.uniqid(), 'keycloak_group_id' => 'kc-gated-'.uniqid()]);
    $this->openRole = Role::create(['name' => 'open-role-'.uniqid(), 'keycloak_group_id' => 'kc-open-'.uniqid()]);

    $this->permission = Permission::create(['name' => 'test-permission-'.uniqid()]);
    $this->gatedRole->givePermissionTo($this->permission);

    $this->position = BoardPosition::create([
        'name' => 'Gated Position',
        'shield_role_id' => $this->gatedRole->id,
        'requires_nda' => true,
    ]);
});

it('keeps the role assigned but withholds its permission when the NDA is unsigned', function () {
    $this->user->assignRole($this->gatedRole);

    expect($this->user->hasRole($this->gatedRole))->toBeTrue()
        ->and($this->user->can($this->permission->name))->toBeFalse();
});

it('grants the permission once the NDA is signed', function () {
    $this->user->assignRole($this->gatedRole);

    BoardPositionSignature::create([
        'user_id' => $this->user->id,
        'board_position_id' => $this->position->id,
        'signed_at' => now(),
    ]);

    expect($this->user->can($this->permission->name))->toBeTrue();
});

it('withholds the permission again if the signature is unmarked', function () {
    $this->user->assignRole($this->gatedRole);

    $signature = BoardPositionSignature::create([
        'user_id' => $this->user->id,
        'board_position_id' => $this->position->id,
        'signed_at' => now(),
    ]);

    expect($this->user->can($this->permission->name))->toBeTrue();

    $signature->update(['signed_at' => null]);

    expect($this->user->can($this->permission->name))->toBeFalse();
});

it('still grants a permission also reachable via a non-gated role', function () {
    $this->openRole->givePermissionTo($this->permission);

    $this->user->assignRole([$this->gatedRole, $this->openRole]);

    expect($this->user->can($this->permission->name))->toBeTrue();
});

it('does not gate a role that is not tied to any requires_nda position', function () {
    $this->openRole->givePermissionTo($this->permission);

    $this->user->assignRole($this->openRole);

    expect($this->user->can($this->permission->name))->toBeTrue();
});

it('does not gate a role tied to a position that does not require an NDA', function () {
    $this->position->update(['requires_nda' => false]);

    $this->user->assignRole($this->gatedRole);

    expect($this->user->can($this->permission->name))->toBeTrue();
});
