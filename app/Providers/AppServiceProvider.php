<?php

namespace App\Providers;

use App\Listeners\LogSentEmail;
use App\Policies\ActivityPolicy;
use App\Providers\auth\KeycloakProvider;
use Illuminate\Mail\Events\MessageSent;
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

        // Logs every outbound mail to sent_emails - see LogSentEmail.
        Event::listen(MessageSent::class, LogSentEmail::class);
    }
}
