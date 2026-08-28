<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks authenticated requests until the user has accepted the current
 * privacy policy - "current" meaning accepted_at is no older than the
 * policy's updated_at, so any admin edit (including an English-only
 * translation fix) requires every resident to re-accept. See
 * User::hasAcceptedCurrentPrivacyPolicy().
 */
class EnsureUserAcceptedPrivacyPolicy
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->hasAcceptedCurrentPrivacyPolicy()) {
            return response()->json([
                'errors' => 'PRIVACY_POLICY_NOT_ACCEPTED',
                'message' => 'You must accept the current privacy policy before continuing.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
