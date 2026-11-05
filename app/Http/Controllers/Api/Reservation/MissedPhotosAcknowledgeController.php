<?php

namespace App\Http\Controllers\Api\Reservation;

use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MissedPhotosAcknowledgeController extends Controller
{
    /**
     * Acknowledge missed photos
     *
     * Dismiss the missed-photos popup for the given reservations. Takes the
     * ids the app actually showed, rather than "all of them", so a
     * reservation that became missed in between isn't dismissed unseen. Ids
     * that aren't the user's own are ignored.
     *
     * @authenticated
     */
    #[Group('Reservation')]
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reservation_ids' => ['required', 'array', 'max:100'],
            'reservation_ids.*' => ['integer'],
        ]);

        $request->user()
            ->reservations()
            ->whereIn('id', $validated['reservation_ids'])
            ->whereNull('missed_photos_acknowledged_at')
            ->update(['missed_photos_acknowledged_at' => now()]);

        return response()->json(['message' => 'Acknowledged.']);
    }
}
