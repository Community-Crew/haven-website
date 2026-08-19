<?php

namespace App\Http\Controllers\Api\Reservation;

use App\Http\Controllers\Controller;
use App\Mail\ReservationCancelledMail;
use App\Models\Reservation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class ReservationCancelController extends Controller
{
    public function __invoke(Reservation $reservation)
    {
        Gate::authorize('update', $reservation);

        if ($reservation->status === 'cancelled') {
            return response()->json([
                'message' => 'This reservation is already cancelled and cannot be altered.',
            ], ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (now()->parse($reservation->start_at)->isPast()) {
            return response()->json([
                'message' => 'Past reservations are archival and completely immutable.',
            ], 422);
        }

        $reservation->update([
            'status' => 'cancelled',
        ]);

        Mail::to($reservation->user)->send(new ReservationCancelledMail($reservation));

        return response()->json([
            'message' => 'Reservation cancelled successfully.',
            'data' => $reservation,
        ]);
    }
}
