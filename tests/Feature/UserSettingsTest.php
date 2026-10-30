<?php

use App\Http\Middleware\ValidateKeycloakToken;
use App\Models\User;

// ValidateKeycloakToken hits a real Keycloak JWKS endpoint, so it's excluded
// and actingAs() stands in for it - same as ReservationCheckTest.
beforeEach(function () {
    $this->withoutMiddleware([ValidateKeycloakToken::class]);

    $this->user = User::factory()->create([
        'email' => 'settings@example.com',
        'keycloak_id' => 'kc-settings',
    ]);

    $this->actingAs($this->user);
});

it('returns the defaults for a new user', function () {
    $this->getJson('/api/v1/user/settings')
        ->assertOk()
        ->assertExactJson(['data' => ['locale' => 'nl', 'theme' => 'system']]);
});

it('updates only the settings that are sent', function () {
    $this->patchJson('/api/v1/user/settings', ['theme' => 'dark'])
        ->assertOk()
        ->assertExactJson(['data' => ['locale' => 'nl', 'theme' => 'dark']]);

    $this->patchJson('/api/v1/user/settings', ['locale' => 'en'])
        ->assertOk()
        ->assertExactJson(['data' => ['locale' => 'en', 'theme' => 'dark']]);

    expect($this->user->fresh())
        ->locale->toBe('en')
        ->theme->toBe('dark');
});

it('uses the chosen locale for mail', function () {
    $this->patchJson('/api/v1/user/settings', ['locale' => 'en'])->assertOk();

    expect($this->user->fresh()->preferredLocale())->toBe('en');
});

it('rejects unsupported values', function (array $payload, string $field) {
    $this->patchJson('/api/v1/user/settings', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);

    expect($this->user->fresh())
        ->locale->toBe('nl')
        ->theme->toBe('system');
})->with([
    'unknown locale' => [['locale' => 'de'], 'locale'],
    'unknown theme' => [['theme' => 'blue'], 'theme'],
    'non-string theme' => [['theme' => ['dark']], 'theme'],
]);

it('ignores fields that are not settings', function () {
    $this->patchJson('/api/v1/user/settings', ['theme' => 'light', 'email' => 'hijack@example.com', 'activated_at' => now()])
        ->assertOk();

    expect($this->user->fresh())
        ->theme->toBe('light')
        ->email->toBe('settings@example.com')
        ->activated_at->toBeNull();
});

it('works before the user is activated or has accepted the privacy policy', function () {
    expect($this->user->activated_at)->toBeNull()
        ->and($this->user->privacy_policy_accepted_at)->toBeNull();

    $this->patchJson('/api/v1/user/settings', ['locale' => 'en'])->assertOk();
});

it('includes the settings in the user profile', function () {
    $this->user->update(['theme' => 'dark']);

    $this->getJson('/api/v1/user')
        ->assertOk()
        ->assertJsonPath('data.settings', ['locale' => 'nl', 'theme' => 'dark']);
});
