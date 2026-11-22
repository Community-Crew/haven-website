<?php

use App\Enums\CheckVerificationStatus;
use App\Filament\Resources\ReservationChecks\Pages\ListReservationChecks;
use App\Filament\Resources\ReservationChecks\ReservationCheckResource;
use App\Filament\Resources\Reservations\Pages\ListReservations;
use App\Filament\Resources\Reservations\ReservationResource;
use App\Mail\ReservationCheckRejectedMail;
use App\Models\Reservation;
use App\Models\ReservationCheck;
use App\Models\Room;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

// Mirrors ReservationHeatmapPageTest's actingAsReservationHeatmapAdmin() -
// simulates what shield:generate + the deploy sync step would produce,
// without running the artisan command here.
function actingAsReservationCheckAdmin(): User
{
    $admin = User::factory()->create([
        'email' => 'admin@example.com',
        'keycloak_id' => 'kc-check-admin',
    ]);

    foreach (['ViewAny:ReservationCheck', 'View:ReservationCheck', 'Update:ReservationCheck'] as $name) {
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
    }

    filament()->setCurrentPanel(filament()->getPanel('admin'));
    test()->actingAs($admin);

    return $admin;
}

function makeReservationCheck(array $overrides = []): ReservationCheck
{
    $user = User::factory()->create([
        'email' => 'resident-'.uniqid().'@example.com',
        'keycloak_id' => 'kc-resident-'.uniqid(),
    ]);

    $room = Room::create([
        'name' => 'Werkplaats',
        'slug' => 'werkplaats-'.uniqid(),
        'description' => 'A room',
        'location' => 'Ground floor',
        'status' => 'available',
    ]);

    $reservation = Reservation::create([
        'name' => 'Werkplaats sessie',
        'start_at' => now(),
        'end_at' => now()->addHours(2),
        'status' => 'approved',
        'user_id' => $user->id,
        'room_id' => $room->id,
    ]);

    return ReservationCheck::create(array_merge([
        'reservation_id' => $reservation->id,
        'type' => 'check_in',
        'user_id' => $user->id,
        'occurred_at' => now(),
        'verification_status' => CheckVerificationStatus::PENDING,
    ], $overrides));
}

it('renders the review queue, flagged and unflagged alike', function () {
    actingAsReservationCheckAdmin();

    makeReservationCheck();
    makeReservationCheck(['flag_reason' => 'Checked in 42 min before the reservation start.']);

    Livewire::test(ListReservationChecks::class)->assertSuccessful();
});

it('approves a pending check from the table action', function () {
    actingAsReservationCheckAdmin();

    $check = makeReservationCheck();

    Livewire::test(ListReservationChecks::class)
        ->callTableAction('approve', $check);

    expect($check->fresh()->verification_status)->toBe(CheckVerificationStatus::VERIFIED);
});

it('rejects a pending check with a note and emails the resident', function () {
    Mail::fake();
    actingAsReservationCheckAdmin();

    $check = makeReservationCheck();

    Livewire::test(ListReservationChecks::class)
        ->callTableAction('reject', $check, data: ['rejection_note' => 'Photo does not show the room.']);

    $check->refresh();
    expect($check->verification_status)->toBe(CheckVerificationStatus::REJECTED)
        ->and($check->rejection_note)->toBe('Photo does not show the room.');

    Mail::assertSent(
        ReservationCheckRejectedMail::class,
        fn (ReservationCheckRejectedMail $mail) => $mail->hasTo($check->reservation->user->email)
    );
});

it('lists reservations to review, each linking to its own check', function () {
    $admin = actingAsReservationCheckAdmin();
    $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'ViewAny:Reservation', 'guard_name' => 'web']));

    $pending = makeReservationCheck();
    $otherPending = makeReservationCheck();
    $verified = makeReservationCheck(['verification_status' => CheckVerificationStatus::VERIFIED]);

    Livewire::test(ListReservations::class)
        ->set('activeTab', 'to_review')
        ->assertCanSeeTableRecords([$pending->reservation, $otherPending->reservation])
        ->assertCanNotSeeTableRecords([$verified->reservation])
        ->assertTableColumnStateSet('photos', 'To review', $pending->reservation)
        ->assertActionHasUrl(
            TestAction::make('reviewPhoto')->table($pending->reservation),
            ReservationCheckResource::getUrl('review', ['check' => $pending->id]),
        )
        ->assertActionHasUrl(
            TestAction::make('reviewPhoto')->table($otherPending->reservation),
            ReservationCheckResource::getUrl('review', ['check' => $otherPending->id]),
        );
});

it('leaves photo review out of the reservations list without its permission', function () {
    $admin = User::factory()->create(['email' => 'desk@example.com', 'keycloak_id' => 'kc-desk']);
    $admin->givePermissionTo(Permission::firstOrCreate(['name' => 'ViewAny:Reservation', 'guard_name' => 'web']));
    filament()->setCurrentPanel(filament()->getPanel('admin'));
    $this->actingAs($admin);
    $check = makeReservationCheck();

    Livewire::test(ListReservations::class)
        ->assertSuccessful()
        ->assertActionHidden(TestAction::make('reviewPhoto')->table($check->reservation))
        ->assertActionHidden('reviewPhotos');

    expect(ReservationResource::getNavigationBadge())->toBeNull()
        ->and(array_keys(Livewire::test(ListReservations::class)->instance()->getTabs()))->not->toContain('to_review');
});
