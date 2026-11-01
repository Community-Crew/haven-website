<?php

use App\Models\ReservationPolicy;
use App\Models\ReservationPolicyEntry;
use App\Models\Room;
use App\Models\User;
use App\Services\ReservationPolicyService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    // assignRole() would otherwise sync to a real Keycloak.
    Http::fake();

    // Wednesday 2026-11-04, 10:00 - before the 20:00 window rollover.
    Carbon::setTestNow(Carbon::parse('2026-11-04 10:00'));

    $this->user = User::factory()->create([
        'email' => 'policy-tester-'.uniqid().'@example.com',
        'keycloak_id' => 'kc-'.uniqid(),
    ]);

    // keycloak_group_id pre-set so RoleObserver skips Keycloak provisioning.
    $role = Role::create(['name' => 'resident-'.uniqid(), 'keycloak_group_id' => 'kc-'.uniqid()]);
    $this->user->assignRole($role);

    $this->room = Room::create([
        'name' => 'Werkplaats',
        'slug' => 'werkplaats',
        'description' => 'A room',
        'location' => 'Ground floor',
        'status' => 'available',
    ]);

    $this->policy = ReservationPolicy::create(['role_name' => $role->name, 'max_days_in_advance' => 14]);
    $this->policy->forceFill(['shield_role_id' => $role->id])->save();
    $this->policy->rooms()->attach($this->room);

    $this->actingAs($this->user);
});

afterEach(fn () => Carbon::setTestNow());

it('applies each entry on the weekday it was configured for (0 = Monday)', function (int $dayOfWeek, string $date) {
    ReservationPolicyEntry::create([
        'reservation_policy_id' => $this->policy->id,
        'day_of_week' => $dayOfWeek,
        'start_time' => '09:00',
        'end_time' => '17:00',
    ]);

    $service = app(ReservationPolicyService::class);
    $target = Carbon::parse($date);

    expect($service->getUserPolicies($target, $this->room))->toHaveCount(1)
        ->and($service->getUserPolicies($target->copy()->subDay(), $this->room))->toBeEmpty()
        ->and($service->getUserPolicies($target->copy()->addDay(), $this->room))->toBeEmpty();
})->with([
    'Monday' => [0, '2026-11-09'],
    'Tuesday' => [1, '2026-11-10'],
    'Wednesday' => [2, '2026-11-11'],
    'Sunday' => [6, '2026-11-08'],
]);
