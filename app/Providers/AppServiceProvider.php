<?php

namespace App\Providers;

use App\Models\ReservationPolicyEntry;
use App\Policies\ReservationPolicyPolicy;
use App\Providers\auth\KeycloakProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('keycloak', KeycloakProvider::class);
        });
    }
}
