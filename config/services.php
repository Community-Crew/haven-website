<?php

return [
    'keycloak' => [
        'client_id' => env('KEYCLOAK_CLIENT_ID'),
        'client_secret' => env('KEYCLOAK_CLIENT_SECRET'),
        'redirect' => 'http://admin.havencommunity.test/login/callback',
        'base_url' => env('KEYCLOAK_BASE_URL'),   // Specify your keycloak server URL here
        'realm' => env('KEYCLOAK_REALM'),         // Specify your keycloak realm
    ],
];
