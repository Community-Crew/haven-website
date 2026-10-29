<?php

use App\Filament\Resources\Rooms\Schemas\RoomForm;
use App\Filament\Support\MediaCoverPicker;
use App\Filament\Support\MediaGalleryPicker;
use App\Models\Media;
use App\Models\Reservation;
use App\Models\ReservationCheck;
use App\Models\Room;
use App\Models\User;

beforeEach(function () {
    $this->room = Room::create([
        'name' => 'Werkplaats',
        'slug' => 'werkplaats-picker-guard',
        'description' => 'A room',
        'location' => 'Ground floor',
        'status' => 'available',
    ]);

    $resident = User::factory()->create(['email' => 'picker-resident@example.com', 'keycloak_id' => 'kc-picker-resident']);

    $reservation = Reservation::create([
        'name' => 'Werkplaats sessie',
        'start_at' => now(),
        'end_at' => now()->addHours(2),
        'status' => 'approved',
        'user_id' => $resident->id,
        'room_id' => $this->room->id,
    ]);

    $this->check = ReservationCheck::create([
        'reservation_id' => $reservation->id,
        'type' => 'check_in',
        'user_id' => $resident->id,
        'occurred_at' => now(),
        'verification_status' => 'pending',
    ]);

    $this->checkPhoto = Media::create([
        'disk' => 'hetzner',
        'path' => 'media/'.ReservationCheck::PHOTO_COLLECTION.'/resident.webp',
        'collection' => ReservationCheck::PHOTO_COLLECTION,
        'mediable_type' => $this->check->getMorphClass(),
        'mediable_id' => $this->check->id,
    ]);
});

it('never links a resident check photo as a cover, even if its path is submitted', function () {
    MediaCoverPicker::sync($this->room, 'cover', RoomForm::COVER_COLLECTION, ['cover' => $this->checkPhoto->path]);

    expect($this->checkPhoto->fresh()->mediable_id)->toBe($this->check->id)
        ->and($this->checkPhoto->fresh()->mediable_type)->toBe($this->check->getMorphClass());
});

it('leaves resident check photos out of the cover library search', function () {
    $search = (new ReflectionMethod(MediaCoverPicker::class, 'searchResults'))->invoke(null, 'reservation-check');

    expect($search)->not->toHaveKey($this->checkPhoto->path);
});

it('only links fresh uploads or the record\'s own photos in the gallery', function () {
    $fresh = Media::create([
        'disk' => 'hetzner',
        'path' => 'media/'.Room::CHECKIN_EXAMPLE_COLLECTION.'/fresh.webp',
        'collection' => Room::CHECKIN_EXAMPLE_COLLECTION,
    ]);

    MediaGalleryPicker::sync($this->room, 'photos', Room::CHECKIN_EXAMPLE_COLLECTION, [
        'photos' => [$fresh->path, $this->checkPhoto->path],
    ]);

    expect($fresh->fresh()->mediable_id)->toBe($this->room->id)
        ->and($this->checkPhoto->fresh()->mediable_id)->toBe($this->check->id)
        ->and($this->checkPhoto->fresh()->mediable_type)->toBe($this->check->getMorphClass());
});
