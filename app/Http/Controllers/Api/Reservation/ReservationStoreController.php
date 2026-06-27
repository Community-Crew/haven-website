<?php

namespace App\Http\Controllers\Api\Reservation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Reservation\StoreReservationRequest; // 🎯 Updated Namespace
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;

class ReservationStoreController extends Controller
{
    protected ReservationService $reservationService;

    public function __construct(ReservationService $reservationService)
    {
        $this->reservationService = $reservationService;
    }

    public function __invoke(StoreReservationRequest $request): JsonResponse
    {
        $data = $request->validated();

        $reservation = $this->reservationService->createReservation($data);

        return response()->json([
            'success' => true,
            'message' => 'Reservation recorded successfully.',
            'data' => $reservation,
        ], 210);
    }
}
