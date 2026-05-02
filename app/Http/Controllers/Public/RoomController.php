<?php

namespace App\Http\Controllers\Public;

use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Services\ReservationPolicyService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @throws AuthorizationException
     */
    public function index(): Response
    {
        return Inertia::render('rooms/Index', ['rooms' => Room::all()]);
    }

    public function show(Request $request, Room $room, ReservationPolicyService $reservationPolicyService)
    {
        if ($request->user() != null) {
            $reservations = $room->reservations()
                ->whereTodayOrAfter('start_at')
                ->where('status', ReservationStatus::APPROVED->value)
                ->with('user', 'organisation')
                ->orderBy('start_at')
                ->limit(15)->get();
            $formattedReservations = $reservations->map(function ($reservation) use ($request) {
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

        return Inertia::render('rooms/Show',
            [
                'room' => $room,
                'reservations' => $formattedReservations,
                'weeklyPolicies' => $reservationPolicyService->getWeeklySchedule($room),
                'userOrganisations' => $request->user()->organisations ?? null,
            ]);
    }
}
