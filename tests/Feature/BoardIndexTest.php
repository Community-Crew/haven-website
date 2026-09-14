<?php

use App\Models\BoardPosition;
use App\Models\Membership;
use App\Models\MemberType;
use App\Models\Organisation;
use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    // BoardPosition/Organisation creation below best-effort provisions real
    // Keycloak groups via RoleObserver/BoardPositionObserver - fake it,
    // this test only cares about what the public endpoint returns.
    Http::fake();

    $this->memberType = MemberType::create(['name' => 'Regular']);
});

/**
 * UserFactory only sets 'name' (see database/factories/UserFactory.php) -
 * every other feature test in the suite supplies an explicit unique email,
 * so this does too.
 */
function boardTestUser(string $name): User
{
    return User::factory()->create([
        'name' => $name,
        'email' => 'board-test-'.uniqid().'@example.com',
        'keycloak_id' => 'kc-'.uniqid(),
    ]);
}

it('lists a public, currently-open board member, without authentication', function () {
    $organisation = Organisation::create(['name' => 'Garden Commission', 'is_commission' => false]);
    $position = BoardPosition::create(['name' => 'Coordinator', 'organisation_id' => $organisation->id]);
    $user = boardTestUser('Alex Doe');

    Membership::create([
        'user_id' => $user->id,
        'member_type_id' => $this->memberType->id,
        'status' => 'active',
        'board_position_id' => $position->id,
    ]);

    $response = $this->getJson('/api/v1/board');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Alex Doe')
        ->assertJsonPath('data.0.title', 'Coordinator')
        ->assertJsonPath('data.0.organisation', 'Garden Commission');
});

it('reports a global board position\'s organisation as null', function () {
    $position = BoardPosition::create(['name' => 'Voorzitter']);
    $user = boardTestUser('Global Member');

    Membership::create([
        'user_id' => $user->id,
        'member_type_id' => $this->memberType->id,
        'status' => 'active',
        'board_position_id' => $position->id,
    ]);

    $response = $this->getJson('/api/v1/board');

    $response->assertOk()->assertJsonPath('data.0.organisation', null);
});

it('excludes memberships that have no board position', function () {
    Membership::create([
        'user_id' => boardTestUser('No Position')->id,
        'member_type_id' => $this->memberType->id,
        'status' => 'active',
    ]);

    $response = $this->getJson('/api/v1/board');

    $response->assertOk()->assertJsonCount(0, 'data');
});

it('excludes a board position marked not public', function () {
    $position = BoardPosition::create(['name' => 'Voorzitter']);

    Membership::create([
        'user_id' => boardTestUser('Not Public')->id,
        'member_type_id' => $this->memberType->id,
        'status' => 'active',
        'board_position_id' => $position->id,
        'is_public' => false,
    ]);

    $response = $this->getJson('/api/v1/board');

    $response->assertOk()->assertJsonCount(0, 'data');
});

it('excludes a board position held by a non-open (ended) membership', function () {
    $position = BoardPosition::create(['name' => 'Voorzitter']);

    Membership::create([
        'user_id' => boardTestUser('Ended')->id,
        'member_type_id' => $this->memberType->id,
        'status' => 'ended',
        'board_position_id' => $position->id,
    ]);

    $response = $this->getJson('/api/v1/board');

    $response->assertOk()->assertJsonCount(0, 'data');
});

it('orders board members by sort_order', function () {
    $position = BoardPosition::create(['name' => 'Lid']);

    Membership::create([
        'user_id' => boardTestUser('Second')->id,
        'member_type_id' => $this->memberType->id,
        'status' => 'active',
        'board_position_id' => $position->id,
        'sort_order' => 2,
    ]);

    Membership::create([
        'user_id' => boardTestUser('First')->id,
        'member_type_id' => $this->memberType->id,
        'status' => 'active',
        'board_position_id' => $position->id,
        'sort_order' => 1,
    ]);

    $response = $this->getJson('/api/v1/board');

    $response->assertOk()
        ->assertJsonPath('data.0.name', 'First')
        ->assertJsonPath('data.1.name', 'Second');
});
