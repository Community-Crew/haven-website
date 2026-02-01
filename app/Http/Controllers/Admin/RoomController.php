<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Enums\ReservationStatus;
use App\Http\Enums\RoomStatus;
use App\Http\Requests\Admin\UpdateRoomRequest;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Room::class);
        $rooms = Room::all();

        return Inertia::render('dashboard/rooms/Index', ['rooms' => $rooms]);
    }

    public function create() {}

    public function store(Request $request) {}

    public function show(Request $request, Room $room): Response
    {
        Gate::authorize('view', $room);
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
            'filters' => $request->only('status', 'date'),
            'reservations' => $reservations,
        ]);

    }

    public function update(UpdateRoomRequest $request, Room $room): RedirectResponse
    {
        Gate::authorize('update', $room);

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

        return redirect()->back();
    }
}
