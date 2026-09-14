<?php

use App\Models\BoardPosition;
use App\Models\BoardPositionAssignment;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Http::fake();

    $this->user = User::factory()->create([
        'email' => 'assignment-observer-'.uniqid().'@example.com',
        'keycloak_id' => 'kc-'.uniqid(),
    ]);
});

/**
 * The bug this whole table exists to fix: Membership.board_position_id
 * (a single column on the one open membership a user can have) could only
 * ever hold one position at a time. A user genuinely can hold several -
 * chair of one commission, treasurer of the board, and commissioner of
 * digital affairs, simultaneously.
 */
it('grants a role for each of several board positions the same user holds at once', function () {
    $chairRole = Role::create(['name' => 'chair-'.uniqid(), 'keycloak_group_id' => 'kc-'.uniqid()]);
    $treasurerRole = Role::create(['name' => 'treasurer-'.uniqid(), 'keycloak_group_id' => 'kc-'.uniqid()]);
    $digitalRole = Role::create(['name' => 'digital-'.uniqid(), 'keycloak_group_id' => 'kc-'.uniqid()]);

    $chair = BoardPosition::create(['name' => 'Chair', 'shield_role_id' => $chairRole->id]);
    $treasurer = BoardPosition::create(['name' => 'Treasurer', 'shield_role_id' => $treasurerRole->id]);
    $digital = BoardPosition::create(['name' => 'Commissioner of Digital Affairs', 'shield_role_id' => $digitalRole->id]);

    BoardPositionAssignment::create(['user_id' => $this->user->id, 'board_position_id' => $chair->id]);
    BoardPositionAssignment::create(['user_id' => $this->user->id, 'board_position_id' => $treasurer->id]);
    BoardPositionAssignment::create(['user_id' => $this->user->id, 'board_position_id' => $digital->id]);

    $freshUser = $this->user->fresh();

    expect($freshUser->hasRole($chairRole))->toBeTrue()
        ->and($freshUser->hasRole($treasurerRole))->toBeTrue()
        ->and($freshUser->hasRole($digitalRole))->toBeTrue();
});

it('revokes only the ended position, leaving the others the user still holds intact', function () {
    $chairRole = Role::create(['name' => 'chair-'.uniqid(), 'keycloak_group_id' => 'kc-'.uniqid()]);
    $treasurerRole = Role::create(['name' => 'treasurer-'.uniqid(), 'keycloak_group_id' => 'kc-'.uniqid()]);

    $chair = BoardPosition::create(['name' => 'Chair', 'shield_role_id' => $chairRole->id]);
    $treasurer = BoardPosition::create(['name' => 'Treasurer', 'shield_role_id' => $treasurerRole->id]);

    $chairAssignment = BoardPositionAssignment::create(['user_id' => $this->user->id, 'board_position_id' => $chair->id]);
    BoardPositionAssignment::create(['user_id' => $this->user->id, 'board_position_id' => $treasurer->id]);

    $chairAssignment->update(['ended_at' => now()]);

    $freshUser = $this->user->fresh();

    expect($freshUser->hasRole($chairRole))->toBeFalse()
        ->and($freshUser->hasRole($treasurerRole))->toBeTrue();
});

it('re-grants the role when a reinstated assignment has its ended_at cleared', function () {
    $role = Role::create(['name' => 'chair-'.uniqid(), 'keycloak_group_id' => 'kc-'.uniqid()]);
    $position = BoardPosition::create(['name' => 'Chair', 'shield_role_id' => $role->id]);

    $assignment = BoardPositionAssignment::create([
        'user_id' => $this->user->id,
        'board_position_id' => $position->id,
        'ended_at' => now(),
    ]);

    expect($this->user->fresh()->hasRole($role))->toBeFalse();

    $assignment->update(['ended_at' => null]);

    expect($this->user->fresh()->hasRole($role))->toBeTrue();
});

it('revokes the role when an active assignment is deleted outright', function () {
    $role = Role::create(['name' => 'chair-'.uniqid(), 'keycloak_group_id' => 'kc-'.uniqid()]);
    $position = BoardPosition::create(['name' => 'Chair', 'shield_role_id' => $role->id]);

    $assignment = BoardPositionAssignment::create(['user_id' => $this->user->id, 'board_position_id' => $position->id]);

    expect($this->user->fresh()->hasRole($role))->toBeTrue();

    $assignment->delete();

    expect($this->user->fresh()->hasRole($role))->toBeFalse();
});
