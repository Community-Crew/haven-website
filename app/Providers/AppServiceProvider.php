<?php

namespace App\Providers;

use App\Providers\auth\KeycloakProvider;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Socialite;

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
//        Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
//            $event->extendSocialite('keycloak', \SocialiteProviders\Keycloak\Provider::class);
//        });
        Socialite::extend('keycloak', function ($app) {
            $config = $app['config']['services.keycloak'];
            return Socialite::buildProvider(
                KeycloakProvider::class,
                $config
            );
        });
    }
}
