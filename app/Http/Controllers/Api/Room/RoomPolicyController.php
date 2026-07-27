<?php

namespace App\Http\Controllers\Api\Room;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Services\ReservationPolicyService; // Adjust namespace to match your project
use Illuminate\Http\JsonResponse;

class RoomPolicyController extends Controller
{
    protected ReservationPolicyService $policyService;

    public function __construct(ReservationPolicyService $policyService)
    {
        $this->policyService = $policyService;
    }

    /**
     * Fetch the weekly schedule boundaries for a specific room.
     */
    public function __invoke(string $id): JsonResponse
    {
        $room = Room::where('id', $id)->firstOrFail();

        $weeklySchedule = $this->policyService->getWeeklySchedule($room);

        return response()->json([
            'success' => true,
            'data' => $weeklySchedule,
        ]);
    }
}
