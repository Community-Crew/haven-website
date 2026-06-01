<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Http\Resources\Api\RoomResource;
use App\Models\Room;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RoomController extends Controller
{

    /**
     * List All Rooms
     *
     * Fetch a paginated list of all rooms.
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $rooms = Room::paginate(15);

        return RoomResource::collection($rooms);
    }

    /**
     * View a room
     *
     * Retrieve the detailed profile of a specific room via its slug.
     * @param Room $room
     * @return RoomResource
     */
    public function show(Room $room): RoomResource
    {
        return new RoomResource($room);
    }
}
