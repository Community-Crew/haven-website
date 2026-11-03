<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The Vestide panel is Dutch-only, whatever the app locale. Registered as
 * persistent panel middleware so Livewire requests (actions, notifications,
 * table updates) get Dutch too, not just the first page load.
 */
class UseDutchLocale
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale('nl');

        return $next($request);
    }
}
