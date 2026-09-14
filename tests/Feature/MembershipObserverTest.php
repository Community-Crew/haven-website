<?php

use App\Mail\MembershipStatusChangedMail;
use App\Models\BoardPosition;
use App\Models\BoardPositionAssignment;
use App\Models\Membership;
use App\Models\MemberType;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    // $this->user has a keycloak_id, so UserRoleSyncService will attempt
    // the Keycloak sync half of any role grant/revoke below too - fake it
    // rather than hitting a real Keycloak.
    Http::fake();

    $this->user = User::factory()->create([
        'email' => 'member@example.com',
        'keycloak_id' => 'kc-member',
        'locale' => 'nl',
    ]);

    $this->memberType = MemberType::create(['name' => 'Regular']);
});

it('mails the member when their status changes', function () {
    Mail::fake();

    $membership = Membership::create([
        'user_id' => $this->user->id,
        'member_type_id' => $this->memberType->id,
        'status' => 'pending',
    ]);

    $membership->update(['status' => 'active']);

    Mail::assertSent(
        MembershipStatusChangedMail::class,
        fn (MembershipStatusChangedMail $mail) => $mail->hasTo($this->user->email)
    );
});

it('does not mail when nothing but status stays the same', function () {
    Mail::fake();

    $membership = Membership::create([
        'user_id' => $this->user->id,
        'member_type_id' => $this->memberType->id,
        'status' => 'pending',
    ]);

    $membership->update(['notes' => 'Reached out about renewing.']);

    Mail::assertNotSent(MembershipStatusChangedMail::class);
});

it('ends every active board position when the membership status leaves open', function () {
    $chairRole = Role::create(['name' => 'chair-'.uniqid(), 'keycloak_group_id' => 'kc-'.uniqid()]);
    $treasurerRole = Role::create(['name' => 'treasurer-'.uniqid(), 'keycloak_group_id' => 'kc-'.uniqid()]);
    $chair = BoardPosition::create(['name' => 'Chair', 'shield_role_id' => $chairRole->id]);
    $treasurer = BoardPosition::create(['name' => 'Treasurer', 'shield_role_id' => $treasurerRole->id]);

    $membership = Membership::create([
        'user_id' => $this->user->id,
        'member_type_id' => $this->memberType->id,
        'status' => 'active',
    ]);

    $chairAssignment = BoardPositionAssignment::create(['user_id' => $this->user->id, 'board_position_id' => $chair->id]);
    $treasurerAssignment = BoardPositionAssignment::create(['user_id' => $this->user->id, 'board_position_id' => $treasurer->id]);

    expect($this->user->fresh()->hasRole($chairRole))->toBeTrue()
        ->and($this->user->fresh()->hasRole($treasurerRole))->toBeTrue();

    $membership->update(['status' => 'ended']);

    expect($this->user->fresh()->hasRole($chairRole))->toBeFalse()
        ->and($this->user->fresh()->hasRole($treasurerRole))->toBeFalse()
        ->and($chairAssignment->fresh()->ended_at)->not->toBeNull()
        ->and($treasurerAssignment->fresh()->ended_at)->not->toBeNull();
});

it('does not touch board positions when the status change keeps the membership open', function () {
    $role = Role::create(['name' => 'chair-'.uniqid(), 'keycloak_group_id' => 'kc-'.uniqid()]);
    $position = BoardPosition::create(['name' => 'Chair', 'shield_role_id' => $role->id]);

    $membership = Membership::create([
        'user_id' => $this->user->id,
        'member_type_id' => $this->memberType->id,
        'status' => 'pending',
    ]);

    $assignment = BoardPositionAssignment::create(['user_id' => $this->user->id, 'board_position_id' => $position->id]);

    // pending -> active is still "open" both before and after.
    $membership->update(['status' => 'active']);

    expect($assignment->fresh()->ended_at)->toBeNull()
        ->and($this->user->fresh()->hasRole($role))->toBeTrue();
});

it('ends active board positions when a membership holding open status is deleted', function () {
    $role = Role::create(['name' => 'chair-'.uniqid(), 'keycloak_group_id' => 'kc-'.uniqid()]);
    $position = BoardPosition::create(['name' => 'Chair', 'shield_role_id' => $role->id]);

    $membership = Membership::create([
        'user_id' => $this->user->id,
        'member_type_id' => $this->memberType->id,
        'status' => 'active',
    ]);

    $assignment = BoardPositionAssignment::create(['user_id' => $this->user->id, 'board_position_id' => $position->id]);

    $membership->delete();

    expect($this->user->fresh()->hasRole($role))->toBeFalse()
        ->and($assignment->fresh()->ended_at)->not->toBeNull();
});
