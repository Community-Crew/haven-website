<?php

use App\Models\BoardPosition;
use App\Models\BoardPositionSignature;
use App\Models\Membership;
use App\Models\MemberType;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    // The user/role below get real keycloak ids, so UserRoleSyncService
    // would attempt actual Keycloak calls on grant/revoke - fake it, and
    // assert against the faked requests where the test cares that a
    // Keycloak call did (or didn't) actually happen. The token endpoint
    // needs a real-shaped JSON body - KeycloakAdminService::token() has a
    // `: string` return type, and Http::fake()'s bare default (an empty
    // body) would make access_token decode to null and throw a TypeError,
    // which is swallowed by UserRoleSyncService's best-effort catch and
    // would silently hide the group PUT/DELETE never being sent.
    Http::fake([
        '*/protocol/openid-connect/token' => Http::response(['access_token' => 'fake-admin-token']),
        '*' => Http::response(),
    ]);

    $this->user = User::factory()->create([
        'email' => 'nda-role-sync-'.uniqid().'@example.com',
        'keycloak_id' => 'kc-'.uniqid(),
    ]);

    $this->memberType = MemberType::create(['name' => 'Regular']);

    // keycloak_group_id pre-set so RoleObserver::created()'s guard skips
    // its own Keycloak group-provisioning call for this test role.
    $this->role = Role::create(['name' => 'gated-role-'.uniqid(), 'keycloak_group_id' => 'kc-group-'.uniqid()]);
    $this->position = BoardPosition::create([
        'name' => 'Gated Position',
        'shield_role_id' => $this->role->id,
        'requires_nda' => true,
    ]);
});

it('does not grant the role or sync Keycloak when an NDA-gated position is assigned unsigned', function () {
    Membership::create([
        'user_id' => $this->user->id,
        'member_type_id' => $this->memberType->id,
        'status' => 'active',
        'board_position_id' => $this->position->id,
    ]);

    expect($this->user->fresh()->hasRole($this->role))->toBeFalse();

    Http::assertNothingSent();
});

it('grants the role and syncs Keycloak once the NDA signature is created signed', function () {
    Membership::create([
        'user_id' => $this->user->id,
        'member_type_id' => $this->memberType->id,
        'status' => 'active',
        'board_position_id' => $this->position->id,
    ]);

    BoardPositionSignature::create([
        'user_id' => $this->user->id,
        'board_position_id' => $this->position->id,
        'signed_at' => now(),
    ]);

    expect($this->user->fresh()->hasRole($this->role))->toBeTrue();

    Http::assertSent(fn ($request) => str_contains($request->url(), "/users/{$this->user->keycloak_id}/groups/{$this->role->keycloak_group_id}")
        && $request->method() === 'PUT');
});

it('grants the role retroactively when an existing unsigned signature is marked signed', function () {
    Membership::create([
        'user_id' => $this->user->id,
        'member_type_id' => $this->memberType->id,
        'status' => 'active',
        'board_position_id' => $this->position->id,
    ]);

    $signature = BoardPositionSignature::create([
        'user_id' => $this->user->id,
        'board_position_id' => $this->position->id,
        'signed_at' => null,
    ]);

    expect($this->user->fresh()->hasRole($this->role))->toBeFalse();

    $signature->update(['signed_at' => now()]);

    expect($this->user->fresh()->hasRole($this->role))->toBeTrue();
});

it('revokes the role and Keycloak group when the signature is unmarked', function () {
    Membership::create([
        'user_id' => $this->user->id,
        'member_type_id' => $this->memberType->id,
        'status' => 'active',
        'board_position_id' => $this->position->id,
    ]);

    $signature = BoardPositionSignature::create([
        'user_id' => $this->user->id,
        'board_position_id' => $this->position->id,
        'signed_at' => now(),
    ]);

    expect($this->user->fresh()->hasRole($this->role))->toBeTrue();

    $signature->update(['signed_at' => null]);

    expect($this->user->fresh()->hasRole($this->role))->toBeFalse();

    Http::assertSent(fn ($request) => str_contains($request->url(), "/users/{$this->user->keycloak_id}/groups/{$this->role->keycloak_group_id}")
        && $request->method() === 'DELETE');
});

it('revokes the role when a signed signature is deleted', function () {
    Membership::create([
        'user_id' => $this->user->id,
        'member_type_id' => $this->memberType->id,
        'status' => 'active',
        'board_position_id' => $this->position->id,
    ]);

    $signature = BoardPositionSignature::create([
        'user_id' => $this->user->id,
        'board_position_id' => $this->position->id,
        'signed_at' => now(),
    ]);

    expect($this->user->fresh()->hasRole($this->role))->toBeTrue();

    $signature->delete();

    expect($this->user->fresh()->hasRole($this->role))->toBeFalse();
});

it('does not gate a position that does not require an NDA', function () {
    $this->position->update(['requires_nda' => false]);

    Membership::create([
        'user_id' => $this->user->id,
        'member_type_id' => $this->memberType->id,
        'status' => 'active',
        'board_position_id' => $this->position->id,
    ]);

    expect($this->user->fresh()->hasRole($this->role))->toBeTrue();
});

it('ignores a signature for a position the user is no longer actively holding', function () {
    $signature = BoardPositionSignature::create([
        'user_id' => $this->user->id,
        'board_position_id' => $this->position->id,
        'signed_at' => null,
    ]);

    $signature->update(['signed_at' => now()]);

    expect($this->user->fresh()->hasRole($this->role))->toBeFalse();
});
