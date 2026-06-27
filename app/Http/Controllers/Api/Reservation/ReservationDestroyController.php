<?php

namespace App\Http\Controllers\Api\Reservation;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Services\ReservationService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReservationDestroyController extends Controller
{
    protected ReservationService $reservationService;

    public function __construct(ReservationService $reservationService)
    {
        $this->reservationService = $reservationService;
    }

    public function __invoke(Request $request, Reservation $reservation): JsonResponse
    {
        try {
            $this->reservationService->cancelReservation($reservation, $request->user()->id);

            return response()->json([
                'success' => true,
                'message' => 'Reservation cancelled.',
            ]);
        } catch (Exception $exception) {
            $code = $exception->getCode() === 403 ? 403 : 422;

            return response()->json([
                'success' => false,
                'error' => $exception->getMessage(),
            ], $code);
        }
    }
}
