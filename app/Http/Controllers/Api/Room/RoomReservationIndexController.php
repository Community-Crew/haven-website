<?php

namespace App\Http\Controllers\Api\Room;

use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Models\Room;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomReservationIndexController extends Controller
{
    /**
     * Get upcoming approved reservations for a specific room with user privacy masking.
     */
    #[Group('Rooms')]
    public function __invoke(Request $request, Room $room): JsonResponse
    {
        $user = $request->user();

        $reservations = $room->reservations()
            ->where('start_at', '>=', now()->startOfDay())
            ->where('status', ReservationStatus::APPROVED->value)
            ->with(['user', 'organisation'])
            ->orderBy('start_at')
            ->limit(15)
            ->get();

        $formattedReservations = $reservations->map(function ($reservation) {
            $data = [
                'id' => $reservation->id,
                'start_at' => $reservation->start_at,
                'end_at' => $reservation->end_at,
                'name' => $reservation->name,
                'status' => $reservation->status->value,
                'organisation' => $reservation->organisation,
            ];

            if ($reservation->share_user) {
                //            if ($reservation->share_user || ($user && $reservation->user_id === $user->id)) {

                $data['user_name'] = $reservation->user->name;
            }

            return $data;
        });

        return response()->json([
            'success' => true,
            'data' => $formattedReservations,
        ]);
    }
}
