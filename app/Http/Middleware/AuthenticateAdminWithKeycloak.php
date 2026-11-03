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
            // Stands in for Filament's own Authenticate middleware, which is
            // what normally enforces canAccessPanel() - without this, any
            // logged-in user could open any panel (e.g. a resident the
            // Vestide one).
            $user = Filament::auth()->user();
            $panel = Filament::getCurrentPanel();

            if ($panel && ! $user->canAccessPanel($panel)) {
                // Vestide staff landing on the admin panel: send them home
                // rather than showing a bare 403.
                if ($panel->getId() !== 'vestide' && $user->canAccessPanel(Filament::getPanel('vestide'))) {
                    return redirect()->to(Filament::getPanel('vestide')->getUrl());
                }

                abort(403);
            }

            return $next($request);
        }

        if ($request->ajax() || $request->wantsJson() || $request->hasHeader('X-Livewire')) {
            abort(401, 'Unauthorized');
        }

        return redirect()->route('filament.admin.oauth.keycloak.redirect');
    }
}
