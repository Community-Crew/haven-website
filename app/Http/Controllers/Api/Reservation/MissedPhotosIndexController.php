<?php

namespace App\Http\Controllers\Api\Reservation;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MissedPhotosIndexController extends Controller
{
    /**
     * Missed photos
     *
     * The authenticated user's finished reservations where no check-in or
     * check-out photos were uploaded, for the app to show a one-time popup.
     * Dismiss them with the acknowledge endpoint.
     *
     * @authenticated
     */
    #[Group('Reservation')]
    public function __invoke(Request $request): JsonResponse
    {
        $reservations = $request->user()
            ->reservations()
            ->missingPhotos()
            ->with(['room', 'checks.photos'])
            ->orderBy('end_at')
            ->get();

        return response()->json([
            'data' => $reservations->map(fn (Reservation $reservation) => [
                'id' => $reservation->id,
                'name' => $reservation->name,
                'room_name' => $reservation->room?->name,
                'start_at' => $reservation->start_at,
                'end_at' => $reservation->end_at,
                'missing' => $reservation->missingPhotoTypes(),
            ]),
        ]);
    }
}
