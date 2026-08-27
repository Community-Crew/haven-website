<?php

return [
    'admin_domain' => env('ADMIN_DOMAIN', 'admin.havencommunity.test'),

    'frontend_url' => env('FRONTEND_URL', 'https://havencommunity.nl'),

    // Bare host for domain-based route matching (Route::domain()) and the
    // same thing with a scheme for building actual links - see QrCode.
    'qr_domain' => env('QR_DOMAIN', 'qr.havencommunity.test'),
    'qr_app_url' => env('QR_APP_URL', 'https://qr.havencommunity.test'),

    'keycloak' => [
        'client_id' => env('KEYCLOAK_CLIENT_ID'),
        'client_secret' => env('KEYCLOAK_CLIENT_SECRET'),
        'redirect' => env('ADMIN_APP_URL').'/oauth/keycloak/callback',
        'base_url' => env('KEYCLOAK_BASE_URL'),   // Specify your keycloak server URL here
        'realms' => env('KEYCLOAK_REALM'),         // Specify your keycloak realm
    ],
];
