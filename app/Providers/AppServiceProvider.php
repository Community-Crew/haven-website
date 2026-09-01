<?php

namespace App\Providers;

use App\Policies\ActivityPolicy;
use App\Providers\auth\KeycloakProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;
use Spatie\Activitylog\Models\Activity;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('keycloak', KeycloakProvider::class);
        });

        // Login/Logout logging lives in App\Listeners\LogAuthenticationEvents -
        // Laravel auto-discovers it from the typed event parameter, no explicit
        // Event::listen() needed (and would double-fire it if added here).

        // Activity lives in Spatie's own namespace, so Laravel's convention-based
        // policy discovery (App\Models\X -> App\Policies\XPolicy) can't find it.
        Gate::policy(Activity::class, ActivityPolicy::class);

        // Logs every outbound mail to sent_emails - see App\Listeners\LogSentEmail.
        // Also auto-discovered (typed MessageSent parameter on handle()), same as
        // LogAuthenticationEvents above - this explicit registration was a
        // duplicate, silently double-inserting a SentEmail row per mail sent.

        $this->configureReservationRateLimiters();
    }

    /**
     * Stopgap against reservation botting (midnight-slot racing) while a
     * proper fair-queue/lottery replacement is designed. Keyed by user id
     * (set on the request by ValidateKeycloakToken before these routes run)
     * rather than IP, since every caller here is an authenticated resident.
     * Limit::perMinute uses a fixed 60s window, so tripping the limit blocks
     * the user for up to a minute - no custom lockout logic needed.
     */
    protected function configureReservationRateLimiters(): void
    {
        // Store/update/cancel: no legitimate flow submits more than a
        // handful of these a minute; a script racing the midnight window
        // does many times that.
        RateLimiter::for('reservation-writes', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->user()?->id ?: $request->ip())
                ->response(fn () => response()->json([
                    'message' => 'Too many reservation attempts. Please wait a minute and try again.',
                ], 429));
        });

        // Availability polling (room reservations index, weekly schedule):
        // looser, since browsing legitimately refreshes these, but still
        // well below what a tight poll loop would produce.
        RateLimiter::for('reservation-reads', function (Request $request) {
            return Limit::perMinute(30)
                ->by($request->user()?->id ?: $request->ip())
                ->response(fn () => response()->json([
                    'message' => 'Too many requests. Please wait a minute and try again.',
                ], 429));
        });
    }
}
