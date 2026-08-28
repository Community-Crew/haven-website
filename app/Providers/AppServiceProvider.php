<?php

namespace App\Providers;

use App\Policies\ActivityPolicy;
use App\Providers\auth\KeycloakProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
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
    }
}
