<?php

namespace App\Http\Controllers\Public;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Enums\ReservationStatus;
use App\Models\Room;
use App\Services\ReservationPolicyService;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;

class RoomController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @throws AuthorizationException
     */
    public function index()
    {
        return Inertia::render('rooms/Index', ['rooms' => Room::all()]);
    }

    public function show(Request $request, Room $room)
    {
        if ($request->user() != null) {
            $reservations = $room->reservations()
                ->whereTodayOrAfter('start_at')
                ->where('status', ReservationStatus::APPROVED->value)
                ->with('user', 'organisation')
                ->orderBy('start_at')
                ->limit(15)->get();
            $formattedReservations = $reservations->map(function ($reservation) {
                $data = [
                    'id' => $reservation->id,
                    'start_at' => $reservation->start_at,
                    'end_at' => $reservation->end_at,
                    'name' => $reservation->name,
                    'status' => $reservation->status,
                    'organisation' => $reservation->organisation,
                ];

                if ($reservation->share_user || $reservation->user == $request->user()) {
                    $data['user_name'] = $reservation->user->name;
                }

                return $data;
            });
        } else {
            $formattedReservations = [];
        }

        $service = new ReservationPolicyService;
        $policy = [];
        for ($i = 0; $i < 7; $i++) {
            $policy[$i] = $service->getMergedTimeSlotsOnWeekday($i, $room);
        }

        return Inertia::render('rooms/Show',
            [
                'room' => $room,
                'reservations' => $formattedReservations,
                'policy' => $policy,
                'maxDaysInAdvance' => $service->getAllDaysInAdvance($room),
                'userOrganisations' => $request->user()->organisations,
            ]);
    }
}
