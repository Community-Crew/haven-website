<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAdminWithKeycloak
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Filament::auth()->check()) {
            return $next($request);
        }

        if ($request->ajax() || $request->wantsJson() || $request->hasHeader('X-Livewire')) {
            abort(401, 'Unauthorized');
        }

        return redirect()->route('filament.admin.oauth.keycloak.redirect');
    }
}
