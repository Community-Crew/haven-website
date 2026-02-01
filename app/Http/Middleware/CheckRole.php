<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $roles = $request->session()->get('roles');
        if (in_array($role, $roles)) {
            return $next($request);
        }

        return redirect()->back()->with('error', ['Geen permissie ('.$role.' Benodigd)']);
    }
}
