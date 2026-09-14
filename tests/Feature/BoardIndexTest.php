<?php

use App\Models\BoardPosition;
use App\Models\BoardPositionAssignment;
use App\Models\Organisation;
use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    // BoardPosition/Organisation/BoardPositionAssignment creation below
    // best-effort provisions real Keycloak groups via
    // RoleObserver/BoardPositionObserver/BoardPositionAssignmentObserver -
    // fake it, this test only cares about what the public endpoint returns.
    Http::fake();
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

it('lists a public, active board member, without authentication', function () {
    $organisation = Organisation::create(['name' => 'Garden Commission', 'is_commission' => false]);
    $position = BoardPosition::create(['name' => 'Coordinator', 'organisation_id' => $organisation->id]);
    $user = boardTestUser('Alex Doe');

    BoardPositionAssignment::create([
        'user_id' => $user->id,
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

    BoardPositionAssignment::create([
        'user_id' => $user->id,
        'board_position_id' => $position->id,
    ]);

    $response = $this->getJson('/api/v1/board');

    $response->assertOk()->assertJsonPath('data.0.organisation', null);
});

it('lists every position a user holds at once, not just one', function () {
    $chair = BoardPosition::create(['name' => 'Chair']);
    $treasurer = BoardPosition::create(['name' => 'Treasurer']);
    $organisation = Organisation::create(['name' => 'Digital Affairs', 'is_commission' => false]);
    $commissioner = BoardPosition::create(['name' => 'Commissioner', 'organisation_id' => $organisation->id]);
    $user = boardTestUser('Multi Hat');

    BoardPositionAssignment::create(['user_id' => $user->id, 'board_position_id' => $chair->id]);
    BoardPositionAssignment::create(['user_id' => $user->id, 'board_position_id' => $treasurer->id]);
    BoardPositionAssignment::create(['user_id' => $user->id, 'board_position_id' => $commissioner->id]);

    $response = $this->getJson('/api/v1/board');

    $response->assertOk()->assertJsonCount(3, 'data');
    $titles = collect($response->json('data'))->pluck('title')->all();
    expect($titles)->toEqualCanonicalizing(['Chair', 'Treasurer', 'Commissioner']);
});

it('excludes an assignment marked not public', function () {
    $position = BoardPosition::create(['name' => 'Voorzitter']);

    BoardPositionAssignment::create([
        'user_id' => boardTestUser('Not Public')->id,
        'board_position_id' => $position->id,
        'is_public' => false,
    ]);

    $response = $this->getJson('/api/v1/board');

    $response->assertOk()->assertJsonCount(0, 'data');
});

it('excludes an assignment that has been ended', function () {
    $position = BoardPosition::create(['name' => 'Voorzitter']);

    BoardPositionAssignment::create([
        'user_id' => boardTestUser('Ended')->id,
        'board_position_id' => $position->id,
        'ended_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/board');

    $response->assertOk()->assertJsonCount(0, 'data');
});

it('orders board members by sort_order', function () {
    $position = BoardPosition::create(['name' => 'Lid']);

    BoardPositionAssignment::create([
        'user_id' => boardTestUser('Second')->id,
        'board_position_id' => $position->id,
        'sort_order' => 2,
    ]);

    BoardPositionAssignment::create([
        'user_id' => boardTestUser('First')->id,
        'board_position_id' => $position->id,
        'sort_order' => 1,
    ]);

    $response = $this->getJson('/api/v1/board');

    $response->assertOk()
        ->assertJsonPath('data.0.name', 'First')
        ->assertJsonPath('data.1.name', 'Second');
});
