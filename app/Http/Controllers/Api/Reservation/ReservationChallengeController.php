<?php

namespace App\Http\Controllers\Api\Reservation;

use App\Http\Controllers\Controller;
use App\Services\ProofOfWork;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Get a reservation challenge
 *
 * Fetch when the booking form opens, then find a counter for which
 * sha256("<challenge>:<counter>") (hex digest of the UTF-8 string) starts
 * with `difficulty` zero bits, and send both along as `pow_challenge` and
 * `pow_nonce` when creating the reservation. Valid from `min_age` until
 * `expires_in` seconds after issuing, and only once. `difficulty` 0 means
 * the check is off and the fields can be left out.
 */
class ReservationChallengeController extends Controller
{
    #[Group('Reservations')]
    public function __invoke(Request $request, ProofOfWork $proofOfWork): JsonResponse
    {
        return response()->json(['data' => $proofOfWork->issue($request->user())]);
    }
}
