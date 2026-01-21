<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;

class RoomController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
//        $this->authorize('viewAny', Room::class);
        $rooms = Room::all();

        return Inertia::render('dashboard/rooms/Index', ['rooms' => $rooms]);
    }

    public function create()
    {

    }

    public function store(Request $request)
    {

    }

    public function show(Room $room){

    }

    public function edit(Room $room)
    {

    }
}
