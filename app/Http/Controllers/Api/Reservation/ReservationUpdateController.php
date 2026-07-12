<?php

namespace App\Http\Controllers\Api\Reservation;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ReservationUpdateController extends Controller
{
    public function __invoke(Request $request, Reservation $reservation)
    {
        Gate::authorize('update', $reservation);

        if (now()->parse($reservation->start_at)->isPast()) {
            return response()->json([
                'message' => 'Past reservations are archival historical entries and cannot be altered.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY); // 422
        }

        if (in_array($reservation->status, ['cancelled', 'rejected'])) {
            return response()->json([
                'message' => "Cannot update details on a reservation that is currently {$reservation->status}.",
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $reservation->update([
            'name' => $validated['name'],
        ]);

        return response()->json([
            'message' => 'Reservation updated successfully.',
            'data' => $reservation,
        ]);
    }
}
