<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReservationResource;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use function Pest\Laravel\get;

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
        $reservations = Reservation::with('user')
        ->whereBetween('start_at', [Carbon::now()->startOfDay(), Carbon::now()->addDays(7)->endOfDay()])
            ->get();
        return Inertia::render('rooms/Show', ['room' => $room, 'reservations' => ReservationResource::collection($reservations)]);
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
