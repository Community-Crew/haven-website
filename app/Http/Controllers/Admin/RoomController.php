<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Enums\RoomStatus;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;

class RoomController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
//        $this->authorize('viewAny');
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
//        $this->authorize('view', $room);

        return Inertia::render('dashboard/rooms/Show', ['room' => $room]);

    }

    public function edit(Room $room)
    {

    }

    public function update(Request $request, Room $room)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'description' => 'required|string',
            'location' => 'required|string',
            'status' => ['required',new Enum(RoomStatus::class)],
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('image')) {
            if ($room->image_path && Storage::disk('hetzner')->exists($room->image_path)){
                Storage::disk('hetzner')->delete($room->image_path);
            }

            $path = $request->file('image')->store('rooms', 'hetzner');
            $room->image_path = $path;
        }

        $room->name = $request->name;
        $room->description = $request->description;
        $room->location = $request->location;
        $room->status = $request->status;

        $room->save();

        return back();
    }
}
