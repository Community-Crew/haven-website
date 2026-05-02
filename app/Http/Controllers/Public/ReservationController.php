<?php

namespace App\Http\Controllers\Public;

use App\Enums\ReservationStatus;
use App\Http\Requests\Public\StoreReservationRequest;
use App\Models\Reservation;
use App\Services\ReservationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ReservationController extends Controller
{
    use AuthorizesRequests;

    public function store(StoreReservationRequest $request, ReservationService $reservationService): RedirectResponse
    {
        $reservation = $reservationService->createReservation($request->validated());

        return redirect()->route('rooms.show', $reservation->room->slug)->with('success', 'Created!');
    }

    public function update(StoreReservationRequest $request, Reservation $reservation, ReservationService $service): RedirectResponse
    {
        $this->authorize('update', $reservation);
        $service->updateReservation($reservation, $request->validated());

        return redirect()->route('profile')->with('success', 'Reservation updated successfully.');
    }

    public function destroy(Request $request, Reservation $reservation): RedirectResponse
    {
        if ($reservation->user_id !== $request->user()->id) {
            abort(403, 'You can only cancel your own reservations.');
        }
        if ($reservation->start_at < Carbon::now()) {
            abort(403, 'You can only cancel future reservations.');
        }

        $reservation->update([
            'status' => ReservationStatus::CANCELLED,
        ]);

        return redirect()->back()->with('success', 'Reservation cancelled.');
    }
}
