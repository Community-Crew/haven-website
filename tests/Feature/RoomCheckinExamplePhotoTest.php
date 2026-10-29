<?php

use App\Models\Media;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('hetzner');

    $this->room = Room::create([
        'name' => 'Werkplaats',
        'slug' => 'werkplaats-checkin-example',
        'description' => 'A room',
        'location' => 'Ground floor',
        'status' => 'available',
    ]);
});

it('exposes an empty checkin_example_photo_urls array when none is set', function () {
    $user = User::factory()->create(['email' => 'room-photo-user@example.com', 'keycloak_id' => 'kc-room-photo-user']);
    $this->actingAs($user);

    $data = $this->getJson('/api/v1/rooms')->json('data');

    $room = collect($data)->firstWhere('id', $this->room->id);
    expect($room['checkin_example_photo_urls'])->toBe([]);
});

it('exposes every example photo attached to the room, in upload order', function () {
    foreach (['first.webp', 'second.webp'] as $filename) {
        $media = Media::create([
            'disk' => 'hetzner',
            'path' => 'media/'.Room::CHECKIN_EXAMPLE_COLLECTION.'/'.$filename,
            'collection' => Room::CHECKIN_EXAMPLE_COLLECTION,
            'mediable_type' => $this->room->getMorphClass(),
            'mediable_id' => $this->room->id,
        ]);
        Storage::disk('hetzner')->put($media->path, 'fake-bytes');
    }

    $user = User::factory()->create(['email' => 'room-photo-user2@example.com', 'keycloak_id' => 'kc-room-photo-user2']);
    $this->actingAs($user);

    $data = $this->getJson('/api/v1/rooms')->json('data');

    $room = collect($data)->firstWhere('id', $this->room->id);
    expect($room['checkin_example_photo_urls'])->toHaveCount(2);
});
