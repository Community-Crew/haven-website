<?php

use App\Http\Controllers\Api\Room\RoomReservationIndexController;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

// Exercises the controller directly rather than through the route (which
// sits behind ValidateKeycloakToken's real Keycloak JWT check, not something
// this suite fakes - see PrivacyPolicyAcceptanceTest for the same pattern).

function requestRoomReservations(Room $room, User $user, ?string $date = null): array
{
    $request = Request::create(
        "/api/v1/rooms/{$room->id}/reservations",
        'GET',
        $date ? ['date' => $date] : []
    );
    $request->setUserResolver(fn () => $user);

    $response = (new RoomReservationIndexController)->__invoke($request, $room);

    return json_decode($response->getContent(), true);
}

beforeEach(function () {
    $this->user = User::factory()->create([
        'email' => 'scheduler@example.com',
        'keycloak_id' => 'kc-scheduler',
    ]);

    $this->room = Room::create([
        'name' => 'Werkplaats',
        'slug' => 'werkplaats',
        'description' => 'A room',
        'location' => 'Ground floor',
        'status' => 'available',
    ]);
});

it('used to hide a reservation past the 15th upcoming one for a busy room - now finding it by date instead', function () {
    // 15 approved reservations sooner than the one we actually care about -
    // this is exactly what pushed a real reservation out of the old flat
    // "next 15 upcoming for the room" query, regardless of which date the
    // scheduler had selected.
    for ($i = 1; $i <= 15; $i++) {
        Reservation::create([
            'name' => "Filler $i",
            'start_at' => now()->addDays($i)->setTime(9, 0),
            'end_at' => now()->addDays($i)->setTime(10, 0),
            'status' => 'approved',
            'user_id' => $this->user->id,
            'room_id' => $this->room->id,
        ]);
    }

    $targetDate = now()->addDays(20);

    $reservation = Reservation::create([
        'name' => 'The one nobody could find',
        'start_at' => $targetDate->clone()->setTime(14, 0),
        'end_at' => $targetDate->clone()->setTime(15, 0),
        'status' => 'approved',
        'user_id' => $this->user->id,
        'room_id' => $this->room->id,
    ]);

    $data = requestRoomReservations($this->room, $this->user, $targetDate->toDateString());

    expect(collect($data['data'])->pluck('id'))->toContain($reservation->id)
        ->and($data['data'])->toHaveCount(1);
});

it('includes a reservation that runs to midnight on the selected day', function () {
    $day = now()->addDays(3)->startOfDay();

    $reservation = Reservation::create([
        'name' => 'Runs to midnight',
        'start_at' => $day->clone()->setTime(23, 0),
        'end_at' => $day->clone()->addDay(), // 00:00 the next calendar day
        'status' => 'approved',
        'user_id' => $this->user->id,
        'room_id' => $this->room->id,
    ]);

    $data = requestRoomReservations($this->room, $this->user, $day->toDateString());

    expect(collect($data['data'])->pluck('id'))->toContain($reservation->id);
});

it('excludes reservations on other days', function () {
    $day = now()->addDays(5)->startOfDay();

    Reservation::create([
        'name' => 'Day before',
        'start_at' => $day->clone()->subDay()->setTime(10, 0),
        'end_at' => $day->clone()->subDay()->setTime(11, 0),
        'status' => 'approved',
        'user_id' => $this->user->id,
        'room_id' => $this->room->id,
    ]);
    Reservation::create([
        'name' => 'Day after',
        'start_at' => $day->clone()->addDay()->setTime(10, 0),
        'end_at' => $day->clone()->addDay()->setTime(11, 0),
        'status' => 'approved',
        'user_id' => $this->user->id,
        'room_id' => $this->room->id,
    ]);

    $data = requestRoomReservations($this->room, $this->user, $day->toDateString());

    expect($data['data'])->toBeEmpty();
});

it('excludes non-approved reservations regardless of date scoping', function () {
    $day = now()->addDays(2)->startOfDay();

    Reservation::create([
        'name' => 'Still pending',
        'start_at' => $day->clone()->setTime(9, 0),
        'end_at' => $day->clone()->setTime(10, 0),
        'status' => 'pending',
        'user_id' => $this->user->id,
        'room_id' => $this->room->id,
    ]);

    $data = requestRoomReservations($this->room, $this->user, $day->toDateString());

    expect($data['data'])->toBeEmpty();
});

it('rejects a malformed date parameter', function () {
    expect(fn () => requestRoomReservations($this->room, $this->user, 'not-a-date'))
        ->toThrow(ValidationException::class);
});

it('falls back to the next 15 upcoming reservations when no date is given', function () {
    Reservation::create([
        'name' => 'Upcoming',
        'start_at' => now()->addDay()->setTime(9, 0),
        'end_at' => now()->addDay()->setTime(10, 0),
        'status' => 'approved',
        'user_id' => $this->user->id,
        'room_id' => $this->room->id,
    ]);

    $data = requestRoomReservations($this->room, $this->user);

    expect($data['data'])->toHaveCount(1);
});
