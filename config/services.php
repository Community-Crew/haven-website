<?php

return [
    'admin_domain' => env('ADMIN_DOMAIN', 'admin.havencommunity.test'),

    'keycloak' => [
        'client_id' => env('KEYCLOAK_CLIENT_ID'),
        'client_secret' => env('KEYCLOAK_CLIENT_SECRET'),
        'redirect' => env('ADMIN_APP_URL').'/oauth/keycloak/callback',
        'base_url' => env('KEYCLOAK_BASE_URL'),   // Specify your keycloak server URL here
        'realms' => env('KEYCLOAK_REALM'),         // Specify your keycloak realm
    ],
];
