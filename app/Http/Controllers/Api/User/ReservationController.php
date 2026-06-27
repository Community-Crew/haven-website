<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\ReservationIndexRequest;
use App\Http\Resources\Api\ReservationResource;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReservationController extends Controller
{
    /**
     * My Reservation
     *
     * Get a paginated list of reservations belonging strictly to the authenticated user.
     * By default, this endpoint only returns active and upcoming reservations from today onward.
     *
     * @group Reservation
     *
     * @authenticated
     */
    #[Group('Reservation')]
    public function __invoke(ReservationIndexRequest $request): AnonymousResourceCollection
    {
        $reservations = $request->user()
            ->reservations()
            ->withStatus($request->query('status'))
            ->inDateRange($request->query('start_date'), $request->query('end_date'))
            ->forRoomSlug($request->query('room_slug'))
            ->with(['room'])
            ->latest()
            ->paginate(15);

        return ReservationResource::collection($reservations);
    }
}
