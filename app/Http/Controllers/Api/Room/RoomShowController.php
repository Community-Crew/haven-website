<?php

namespace App\Http\Controllers\Api\Room;

use App\Http\Resources\Api\RoomResource;
use App\Models\Room;
use Dedoc\Scramble\Attributes\Group;

/**
 * View a room
 *
 * Retrieve the detailed profile of a specific room via its slug.
 *
 * @param  Room  $room
 * @return RoomResource
 */
class RoomShowController
{
    #[Group('Rooms')]
    public function __invoke(Room $room): RoomResource
    {
        return new RoomResource($room);
    }
}
