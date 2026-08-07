<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

/**
 * Records successful logins/logouts to the activity log. Registered against
 * Laravel's built-in Login/Logout events (see AppServiceProvider) rather
 * than hooked into KeycloakAdminController directly, so it fires no matter
 * which guard/flow authenticates the user. Failed Keycloak logins are
 * logged separately in KeycloakAdminController, since a failed attempt
 * never reaches these events (there's no authenticated user to attach).
 */
class LogAuthenticationEvents
{
    public function handleLogin(Login $event): void
    {
        activity('auth')
            ->causedBy($event->user)
            ->event('login')
            ->withProperties(['guard' => $event->guard, 'ip' => request()->ip()])
            ->log('User logged in');
    }

    public function handleLogout(Logout $event): void
    {
        if (! $event->user) {
            return;
        }

        activity('auth')
            ->causedBy($event->user)
            ->event('logout')
            ->withProperties(['guard' => $event->guard, 'ip' => request()->ip()])
            ->log('User logged out');
    }
}
