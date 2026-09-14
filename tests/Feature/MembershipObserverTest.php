<?php

use App\Mail\MembershipStatusChangedMail;
use App\Models\BoardPosition;
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

it('grants the position role when an open membership is created with one', function () {
    $role = Role::create(['name' => 'chair-'.uniqid(), 'keycloak_group_id' => 'kc-'.uniqid()]);
    $position = BoardPosition::create(['name' => 'Chair', 'shield_role_id' => $role->id]);

    Membership::create([
        'user_id' => $this->user->id,
        'member_type_id' => $this->memberType->id,
        'status' => 'active',
        'board_position_id' => $position->id,
    ]);

    expect($this->user->fresh()->hasRole($role))->toBeTrue();
});

it('revokes the position role when the membership ends', function () {
    $role = Role::create(['name' => 'chair-'.uniqid(), 'keycloak_group_id' => 'kc-'.uniqid()]);
    $position = BoardPosition::create(['name' => 'Chair', 'shield_role_id' => $role->id]);

    $membership = Membership::create([
        'user_id' => $this->user->id,
        'member_type_id' => $this->memberType->id,
        'status' => 'active',
        'board_position_id' => $position->id,
    ]);

    expect($this->user->fresh()->hasRole($role))->toBeTrue();

    $membership->update(['status' => 'ended']);

    expect($this->user->fresh()->hasRole($role))->toBeFalse();
});

it('swaps roles when a membership moves to a different board position', function () {
    $oldRole = Role::create(['name' => 'coordinator-'.uniqid(), 'keycloak_group_id' => 'kc-'.uniqid()]);
    $newRole = Role::create(['name' => 'treasurer-'.uniqid(), 'keycloak_group_id' => 'kc-'.uniqid()]);
    $oldPosition = BoardPosition::create(['name' => 'Coordinator', 'shield_role_id' => $oldRole->id]);
    $newPosition = BoardPosition::create(['name' => 'Treasurer', 'shield_role_id' => $newRole->id]);

    $membership = Membership::create([
        'user_id' => $this->user->id,
        'member_type_id' => $this->memberType->id,
        'status' => 'active',
        'board_position_id' => $oldPosition->id,
    ]);

    $membership->update(['board_position_id' => $newPosition->id]);

    $freshUser = $this->user->fresh();

    expect($freshUser->hasRole($oldRole))->toBeFalse()
        ->and($freshUser->hasRole($newRole))->toBeTrue();
});

it('revokes the position role when a membership holding one is deleted', function () {
    $role = Role::create(['name' => 'chair-'.uniqid(), 'keycloak_group_id' => 'kc-'.uniqid()]);
    $position = BoardPosition::create(['name' => 'Chair', 'shield_role_id' => $role->id]);

    $membership = Membership::create([
        'user_id' => $this->user->id,
        'member_type_id' => $this->memberType->id,
        'status' => 'active',
        'board_position_id' => $position->id,
    ]);

    $membership->delete();

    expect($this->user->fresh()->hasRole($role))->toBeFalse();
});
