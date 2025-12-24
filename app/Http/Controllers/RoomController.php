<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Services\ReservationPolicyService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RoomController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     * @throws AuthorizationException
     */
    public function index()
    {
        return Inertia::render('rooms/Index', ['rooms' => Room::all()]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Room $room)
    {
        $reservations = $room->reservations()
            ->whereTodayOrAfter('start_at')
            ->with('user')
            ->orderBy('start_at')
            ->limit(15)->get();

        $formattedReservations = $reservations->map(function ($reservation) {
            $data = [
                'id' => $reservation->id,
                'start_at' => $reservation->start_at,
                'end_at' => $reservation->end_at,
                'name' => $reservation->name,
                'status' => $reservation->status,
            ];

            if ($reservation->share_user || $reservation->user == auth()->user()) {
                $data['user_name'] = $reservation->user->name;
            }

            return $data;
        });


        $service = new ReservationPolicyService();
        $policy = [];
        for ($i = 0; $i < 7; $i++) {
            $policy[$i] = $service->getMergedTimeSlots($i);
        }
        return Inertia::render('rooms/Show',
            [
                'room' => $room,
                'reservations' => $formattedReservations,
                'policy' => $policy,
                'maxDaysInAdvance' => $service->getDaysInAdvance()
            ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Room $room)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Room $room)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Room $room)
    {
        //
    }
}
