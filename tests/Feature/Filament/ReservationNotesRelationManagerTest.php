<?php

use App\Filament\Resources\Reservations\Pages\ViewReservation;
use App\Filament\Resources\Reservations\RelationManagers\NotesRelationManager;
use App\Models\Reservation;
use App\Models\ReservationNote;
use App\Models\Room;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

function actingAsReservationNoteAdmin(): User
{
    $admin = User::factory()->create([
        'email' => 'note-admin@example.com',
        'keycloak_id' => 'kc-note-admin',
    ]);

    foreach (['View:Reservation', 'ViewAny:ReservationNote', 'Create:ReservationNote', 'Update:ReservationNote', 'Delete:ReservationNote'] as $name) {
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
    }

    filament()->setCurrentPanel(filament()->getPanel('admin'));
    test()->actingAs($admin);

    return $admin;
}

beforeEach(function () {
    $resident = User::factory()->create([
        'email' => 'noted-resident@example.com',
        'keycloak_id' => 'kc-noted-resident',
    ]);

    $room = Room::create([
        'name' => 'Werkplaats',
        'slug' => 'werkplaats',
        'description' => 'A room',
        'location' => 'Ground floor',
        'status' => 'available',
    ]);

    $this->reservation = Reservation::create([
        'name' => 'Werkplaats sessie',
        'start_at' => now(),
        'end_at' => now()->addHours(2),
        'status' => 'approved',
        'user_id' => $resident->id,
        'room_id' => $room->id,
    ]);
});

it('creates a note with the acting admin recorded as the author', function () {
    $admin = actingAsReservationNoteAdmin();

    Livewire::test(NotesRelationManager::class, [
        'ownerRecord' => $this->reservation,
        'pageClass' => ViewReservation::class,
    ])
        ->callTableAction('create', data: [
            'body' => 'Resident asked for an extra key.',
            'visible_to_resident' => false,
        ]);

    $note = ReservationNote::sole();
    expect($note->body)->toBe('Resident asked for an extra key.')
        ->and($note->user_id)->toBe($admin->id)
        ->and($note->visible_to_resident)->toBeFalse();
});

it('can post a note already flagged visible to the resident', function () {
    actingAsReservationNoteAdmin();

    Livewire::test(NotesRelationManager::class, [
        'ownerRecord' => $this->reservation,
        'pageClass' => ViewReservation::class,
    ])
        ->callTableAction('create', data: [
            'body' => 'Heads up: the room will be repainted next week.',
            'visible_to_resident' => true,
        ]);

    expect(ReservationNote::sole()->visible_to_resident)->toBeTrue();
});

it('does not let an admin without update permission flip a note\'s visibility', function () {
    $note = ReservationNote::create([
        'reservation_id' => $this->reservation->id,
        'body' => 'Internal only.',
        'visible_to_resident' => false,
    ]);

    $viewer = User::factory()->create(['email' => 'note-viewer@example.com', 'keycloak_id' => 'kc-note-viewer']);
    foreach (['View:Reservation', 'ViewAny:ReservationNote'] as $name) {
        $viewer->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
    }
    filament()->setCurrentPanel(filament()->getPanel('admin'));
    test()->actingAs($viewer);

    Livewire::test(NotesRelationManager::class, [
        'ownerRecord' => $this->reservation,
        'pageClass' => ViewReservation::class,
    ])->call('updateTableColumnState', 'visible_to_resident', (string) $note->id, true);

    expect($note->fresh()->visible_to_resident)->toBeFalse();
});
