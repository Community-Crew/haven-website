<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Like EnsureUserIsActivated, but also lets former residents through: they
 * have no unit, yet can still become or stay a beneficiary from their profile.
 */
class EnsureUserCanManageMembership
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->activated_at && ! ($user->moved_out_at && ! $user->anonymized_at)) {
            return response()->json([
                'errors' => 'ACCOUNT_NOT_ACTIVATED',
                'message' => 'Your account is not activated.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
