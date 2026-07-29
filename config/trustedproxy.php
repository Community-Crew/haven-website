<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | Read by Illuminate\Http\Middleware\TrustProxies::handle() at request
    | time (config('trustedproxy.proxies') is its built-in fallback when no
    | value has been set via TrustProxies::at()). Needed so requests forwarded
    | by a TLS-terminating reverse proxy over plain HTTP are still recognized
    | as HTTPS (X-Forwarded-Proto). Comma-separated IP list, or null to trust
    | nothing (e.g. local dev with no proxy in front).
    |
    */

    'proxies' => env('TRUSTED_PROXIES'),

];
