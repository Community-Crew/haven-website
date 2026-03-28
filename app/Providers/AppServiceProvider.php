<?php

namespace App\Providers;

use App\Providers\auth\KeycloakProvider;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Socialite\Socialite;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('support_id', function () {
            return 'RES-' . strtoupper(Str::random(8));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Socialite::extend('keycloak', function ($app) {
            $config = $app['config']['services.keycloak'];

            return Socialite::buildProvider(
                KeycloakProvider::class,
                $config
            );
        });
    }
}
