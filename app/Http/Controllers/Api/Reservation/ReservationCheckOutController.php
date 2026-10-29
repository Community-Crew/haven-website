<?php

namespace App\Http\Controllers\Api\Reservation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Reservation\CheckReservationRequest;
use App\Http\Resources\Api\ReservationCheckResource;
use App\Models\Reservation;
use App\Services\ReservationCheckService;
use Illuminate\Support\Facades\Gate;

class ReservationCheckOutController extends Controller
{
    public function __construct(protected ReservationCheckService $checkService) {}

    public function __invoke(CheckReservationRequest $request, Reservation $reservation)
    {
        Gate::authorize('update', $reservation);

        $check = $this->checkService->checkOut($reservation, $request->file('photos'));

        return response()->json([
            'message' => 'Checked out successfully. Your photos are pending verification.',
            'data' => new ReservationCheckResource($check),
        ], 201);
    }
}
