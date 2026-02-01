<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Enums\ReservationStatus;
use App\Http\Enums\RoomStatus;
use App\Models\Reservation;
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
        $this->authorize('viewAny', Room::class);
        $rooms = Room::all();

        return Inertia::render('dashboard/rooms/Index', ['rooms' => $rooms]);
    }

    public function create()
    {

    }

    public function store(Request $request)
    {

    }

    public function show(Request $request, Room $room)
    {
        $this->authorize('view', $room);
        $query = Reservation::query()->with('room', 'user');

        $query->where('room_id', $room->id);
        $query->orderBy('start_at');

        $query->whereDate('start_at', '>=', $request->input('date') ?: now());
        $query->when($request->input('status'), function ($query, $status) {
            $query->where('status', $status);
        });

        $reservations = $query->paginate(5)->withQueryString();

        return Inertia::render('dashboard/rooms/Show', [
            'room' => $room,
            'statusOptions' => RoomStatus::cases(),
            'reservationStatusOptions' => ReservationStatus::cases(),
            'filters' => request()->only('status', 'date'),
            'reservations' => $reservations
        ]);

    }

    public function update(Request $request, Room $room)
    {
        $this->authorize('update', $room);

        $request->validate([
            'name' => 'required|string',
            'description' => 'required|string',
            'location' => 'required|string',
            'status' => ['required', new Enum(RoomStatus::class)],
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5000',
        ]);

        if ($request->hasFile('image')) {
            if ($room->image_path && Storage::disk('hetzner')->exists($room->image_path)) {
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
