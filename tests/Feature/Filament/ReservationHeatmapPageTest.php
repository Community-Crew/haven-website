<?php

use App\Filament\Pages\ReservationHeatmap as ReservationHeatmapPage;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

// Mirrors what `shield:generate --option=permissions` + the deploy sync step
// produce in production, without actually running the artisan command here.
function actingAsReservationHeatmapAdmin(): User
{
    $admin = User::factory()->create([
        'email' => 'admin@example.com',
        'keycloak_id' => 'kc-heatmap-admin',
    ]);

    $permission = Permission::firstOrCreate(['name' => 'View:ReservationHeatmap', 'guard_name' => 'web']);
    $admin->givePermissionTo($permission);

    filament()->setCurrentPanel(filament()->getPanel('admin'));
    test()->actingAs($admin);

    return $admin;
}

function makeHeatmapRoom(string $name, string $slug): Room
{
    return Room::create([
        'name' => $name,
        'slug' => $slug,
        'description' => 'A room',
        'location' => 'Ground floor',
        'status' => 'available',
    ]);
}

it('rejects users without the View:ReservationHeatmap permission', function () {
    $user = User::factory()->create([
        'email' => 'resident@example.com',
        'keycloak_id' => 'kc-heatmap-resident',
    ]);

    filament()->setCurrentPanel(filament()->getPanel('admin'));
    test()->actingAs($user);

    expect(ReservationHeatmapPage::canAccess())->toBeFalse();
});

it('renders the combined heatmap for the last 3 months, ignoring old and non-approved reservations', function () {
    $admin = actingAsReservationHeatmapAdmin();

    $room = makeHeatmapRoom('Werkplaats', 'werkplaats');

    Reservation::factory()->create([
        'user_id' => $admin->id,
        'room_id' => $room->id,
        'status' => 'approved',
        'start_at' => now()->subWeek()->setTime(14, 0),
        'end_at' => now()->subWeek()->setTime(15, 0),
    ]);
    Reservation::factory()->create([
        'user_id' => $admin->id,
        'room_id' => $room->id,
        'status' => 'pending',
        'start_at' => now()->subWeek()->setTime(14, 0),
        'end_at' => now()->subWeek()->setTime(15, 0),
    ]);
    Reservation::factory()->create([
        'user_id' => $admin->id,
        'room_id' => $room->id,
        'status' => 'approved',
        'start_at' => now()->subMonths(4)->setTime(14, 0),
        'end_at' => now()->subMonths(4)->setTime(15, 0),
    ]);

    Livewire::test(ReservationHeatmapPage::class)
        ->assertOk()
        ->assertSee('1 approved reservation(s) in the last 3 months');
});

it('shows a small-multiples heatmap card per room', function () {
    $admin = actingAsReservationHeatmapAdmin();

    $roomA = makeHeatmapRoom('Werkplaats', 'werkplaats');
    $roomB = makeHeatmapRoom('Muziekkamer', 'muziekkamer');

    Reservation::factory()->create([
        'user_id' => $admin->id,
        'room_id' => $roomA->id,
        'status' => 'approved',
        'start_at' => now()->subDays(2)->setTime(10, 0),
        'end_at' => now()->subDays(2)->setTime(11, 0),
    ]);
    Reservation::factory()->create([
        'user_id' => $admin->id,
        'room_id' => $roomB->id,
        'status' => 'approved',
        'start_at' => now()->subDays(3)->setTime(10, 0),
        'end_at' => now()->subDays(3)->setTime(11, 0),
    ]);

    Livewire::test(ReservationHeatmapPage::class)
        ->assertOk()
        ->assertSee('Werkplaats')
        ->assertSee('Muziekkamer');
});
