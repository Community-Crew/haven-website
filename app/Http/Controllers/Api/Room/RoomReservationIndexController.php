<?php

namespace App\Http\Controllers\Api\Room;

use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Models\Room;
use Carbon\Carbon;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomReservationIndexController extends Controller
{
    /**
     * Get approved reservations for a specific room, with user privacy masking.
     *
     * Pass `date` (Y-m-d) to scope to that single calendar day - what the
     * booking scheduler actually displays. Without it, falls back to the
     * next 15 upcoming reservations for any other API consumer.
     */
    #[Group('Rooms')]
    public function __invoke(Request $request, Room $room): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $date = $request->query('date');

        $query = $room->reservations()
            ->where('status', ReservationStatus::APPROVED->value)
            ->with(['user', 'organisation'])
            ->orderBy('start_at');

        if ($date) {
            // Overlap-based, not "start_at falls on this day" - a slot
            // booked to run to midnight (24:00) is stored with end_at on
            // the next calendar day, and should still show up here. This
            // used to be a flat "next 15 upcoming reservations for the
            // room" with no date filter at all, so the scheduler's
            // client-side per-day filtering silently dropped any
            // reservation past the 15th upcoming one for a busy room -
            // reported as "reservation not showing in the scheduler".
            $dayStart = Carbon::parse($date)->startOfDay();
            $dayEnd = Carbon::parse($date)->endOfDay();

            $query->where('start_at', '<', $dayEnd)
                ->where('end_at', '>', $dayStart);
        } else {
            $query->where('start_at', '>=', now()->startOfDay())
                ->limit(15);
        }

        $reservations = $query->get();

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
