<?php

namespace App\Http\Controllers\Api\Room;

use App\Http\Resources\Api\RoomResource;
use App\Models\Room;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RoomIndexController
{
    /**
     * List All Rooms
     *
     * Fetch a paginated list of all rooms.
     */
    #[Group('Rooms')]
    public function __invoke(): AnonymousResourceCollection
    {
        $rooms = Room::paginate(15);

        return RoomResource::collection($rooms);
    }
}
