<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActivated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->activated_at) {
            return response()->json([
                'errors' => 'ACCOUNT_NOT_ACTIVATED',
                'message' => 'Your account is not activated.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
