<?php

use App\Http\Controllers\QrRedirectController;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Dynamic QR code redirects, on their own domain so the scanned
            // URL stays short - deliberately outside the api/web split
            // (no /api prefix, no session/CSRF - it's a public GET redirect).
            Route::domain(config('services.qr_domain'))
                ->middleware([SubstituteBindings::class])
                ->get('/{qrCode:code}', QrRedirectController::class)
                ->name('qr.redirect');
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Trusted proxy IPs come from config/trustedproxy.php (TRUSTED_PROXIES
        // env var), read lazily by Illuminate\Http\Middleware\TrustProxies
        // itself at request time - it's already in Laravel's default global
        // middleware stack, so no explicit registration is needed here.
        // (Deliberately NOT $middleware->trustProxies(at: ...) here: this
        // closure runs as part of Application::configure()->create(), before
        // the config repository is bound in the container, so config()/env()
        // calls fail or silently return null at this point.)

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {})
    ->create();
