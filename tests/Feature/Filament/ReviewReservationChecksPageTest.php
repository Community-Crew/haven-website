<?php

use App\Enums\CheckVerificationStatus;
use App\Filament\Resources\ReservationChecks\Pages\ReviewReservationChecks;
use App\Mail\ReservationCheckRejectedMail;
use App\Models\Reservation;
use App\Models\ReservationCheck;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

function actingAsCheckReviewer(array $permissions = ['ViewAny:ReservationCheck', 'View:ReservationCheck', 'Update:ReservationCheck']): User
{
    $admin = User::factory()->create([
        'email' => 'reviewer-'.uniqid().'@example.com',
        'keycloak_id' => 'kc-reviewer-'.uniqid(),
    ]);

    foreach ($permissions as $name) {
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
    }

    filament()->setCurrentPanel(filament()->getPanel('admin'));
    test()->actingAs($admin);

    return $admin;
}

function makeCheckToReview(array $overrides = []): ReservationCheck
{
    $resident = User::factory()->create([
        'email' => 'review-resident-'.uniqid().'@example.com',
        'keycloak_id' => 'kc-review-resident-'.uniqid(),
    ]);

    $room = Room::create([
        'name' => 'Werkplaats',
        'slug' => 'werkplaats-review-'.uniqid(),
        'description' => 'A room',
        'location' => 'Ground floor',
        'status' => 'available',
    ]);

    $reservation = Reservation::create([
        'name' => 'Werkplaats sessie',
        'start_at' => now()->subHours(2),
        'end_at' => now(),
        'status' => 'approved',
        'user_id' => $resident->id,
        'room_id' => $room->id,
    ]);

    return ReservationCheck::create(array_merge([
        'reservation_id' => $reservation->id,
        'type' => 'check_in',
        'user_id' => $resident->id,
        'occurred_at' => now()->subHours(2),
        'verification_status' => CheckVerificationStatus::PENDING,
    ], $overrides));
}

it('starts with flagged checks before older unflagged ones', function () {
    actingAsCheckReviewer();

    makeCheckToReview();
    $flagged = makeCheckToReview(['flag_reason' => 'Checked in 42 min before the reservation start.']);

    Livewire::test(ReviewReservationChecks::class)
        ->assertSuccessful()
        ->assertSet('checkId', $flagged->id)
        ->assertSee('Checked in 42 min before the reservation start.');
});

it('renders a check-out next to its check-in', function () {
    actingAsCheckReviewer();

    $checkIn = makeCheckToReview(['verification_status' => CheckVerificationStatus::VERIFIED]);
    makeCheckToReview([
        'reservation_id' => $checkIn->reservation_id,
        'type' => 'check_out',
        'occurred_at' => now(),
    ]);

    Livewire::test(ReviewReservationChecks::class)
        ->assertSuccessful()
        ->assertSee('At check-in')
        ->assertSee('How the room should look');
});

it('approves the current check and moves on to the next one', function () {
    $admin = actingAsCheckReviewer();

    $first = makeCheckToReview(['flag_reason' => 'Flagged']);
    $second = makeCheckToReview();

    Livewire::test(ReviewReservationChecks::class)
        ->callAction('approve')
        ->assertSet('checkId', $second->id)
        ->assertSet('reviewedCount', 1);

    $first->refresh();
    expect($first->verification_status)->toBe(CheckVerificationStatus::VERIFIED)
        ->and($first->reviewed_by)->toBe($admin->id);
});

it('rejects with a reason and emails the resident', function () {
    Mail::fake();
    actingAsCheckReviewer();

    $check = makeCheckToReview();

    Livewire::test(ReviewReservationChecks::class)
        ->callAction('reject', data: ['rejection_note' => 'Dishes left in the sink.'])
        ->assertHasNoActionErrors()
        ->assertSet('checkId', null)
        ->assertSee('All caught up');

    expect($check->fresh()->verification_status)->toBe(CheckVerificationStatus::REJECTED)
        ->and($check->fresh()->rejection_note)->toBe('Dishes left in the sink.');
    Mail::assertSent(ReservationCheckRejectedMail::class);
});

it('requires a reason to reject', function () {
    actingAsCheckReviewer();

    $check = makeCheckToReview();

    Livewire::test(ReviewReservationChecks::class)
        ->callAction('reject', data: ['rejection_note' => ''])
        ->assertHasActionErrors(['rejection_note' => 'required']);

    expect($check->fresh()->verification_status)->toBe(CheckVerificationStatus::PENDING);
});

it('skips a check without reviewing it', function () {
    actingAsCheckReviewer();

    $first = makeCheckToReview(['flag_reason' => 'Flagged']);
    $second = makeCheckToReview();

    Livewire::test(ReviewReservationChecks::class)
        ->callAction('skip')
        ->assertSet('checkId', $second->id)
        ->assertSet('skipped', [$first->id])
        ->assertSet('reviewedCount', 0);

    expect($first->fresh()->verification_status)->toBe(CheckVerificationStatus::PENDING);
});

it('does not overwrite a check another admin reviewed in the meantime', function () {
    actingAsCheckReviewer();

    $check = makeCheckToReview();
    $page = Livewire::test(ReviewReservationChecks::class)->assertSet('checkId', $check->id);

    $other = User::factory()->create(['email' => 'other-reviewer@example.com', 'keycloak_id' => 'kc-other-reviewer']);
    $check->update(['verification_status' => CheckVerificationStatus::REJECTED, 'reviewed_by' => $other->id, 'rejection_note' => 'Messy.']);

    $page->callAction('approve')->assertSet('checkId', null);

    expect($check->fresh()->verification_status)->toBe(CheckVerificationStatus::REJECTED)
        ->and($check->fresh()->reviewed_by)->toBe($other->id);
});

it('opens a specific check via deep link, even an already reviewed one', function () {
    actingAsCheckReviewer();

    makeCheckToReview();
    $reviewed = makeCheckToReview(['verification_status' => CheckVerificationStatus::VERIFIED]);

    Livewire::withQueryParams(['check' => $reviewed->id])
        ->test(ReviewReservationChecks::class)
        ->assertSet('checkId', $reviewed->id)
        ->assertActionHidden('approve')
        ->assertActionVisible('reject')
        ->assertDontSeeHtml("mountAction('approve')")
        ->assertDontSeeHtml("mountAction('skip')")
        ->assertSeeHtml("mountAction('reject')");
});

it('hides approve and reject from admins who can only view checks', function () {
    actingAsCheckReviewer(['ViewAny:ReservationCheck', 'View:ReservationCheck']);

    $check = makeCheckToReview();

    Livewire::test(ReviewReservationChecks::class)
        ->assertSuccessful()
        ->assertActionHidden('approve')
        ->assertActionHidden('reject')
        ->assertDontSeeHtml("mountAction('approve')")
        ->assertDontSeeHtml("mountAction('reject')")
        ->assertDontSee('approve</span>', false);

    expect($check->fresh()->verification_status)->toBe(CheckVerificationStatus::PENDING);
});

it('is not reachable without access to check photos', function () {
    actingAsCheckReviewer(['View:Reservation']);

    Livewire::test(ReviewReservationChecks::class)->assertForbidden();
});
