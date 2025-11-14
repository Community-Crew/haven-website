<?php
return [
    'authServerUrl' => env('KEYCLOAK_BASE_URL'),
    'realm' => env('KEYCLOAK_REALM'),
    'client_id' => env('KEYCLOAK_ADMIN_CLIENT_ID'),
    'client_secret' => env('KEYCLOAK_ADMIN_CLIENT_SECRET'),
];
