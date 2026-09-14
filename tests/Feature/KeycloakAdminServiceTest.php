<?php

use App\Services\Keycloak\KeycloakAdminService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

it('throws a clear error, and logs it, when the token response has no access_token', function () {
    Http::fake([
        '*/protocol/openid-connect/token' => Http::response(['error' => 'unauthorized_client'], 200),
    ]);

    Log::spy();

    expect(fn () => app(KeycloakAdminService::class)->addUserToGroup('kc-user', 'kc-group'))
        ->toThrow(RuntimeException::class, 'Keycloak admin token response missing access_token');

    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(fn (string $message) => $message === 'Keycloak admin token response missing access_token');
});

it('fetches and uses the token normally when the response is well-formed', function () {
    Http::fake([
        '*/protocol/openid-connect/token' => Http::response(['access_token' => 'a-real-token']),
        '*' => Http::response(),
    ]);

    app(KeycloakAdminService::class)->addUserToGroup('kc-user', 'kc-group');

    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer a-real-token')
        && str_contains($request->url(), '/users/kc-user/groups/kc-group'));
});
