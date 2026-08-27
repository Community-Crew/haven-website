<?php

use App\Models\PrivacyPolicy;

it('serves the seeded privacy policy publicly, without authentication', function () {
    $response = $this->getJson('/api/v1/privacy-policy');

    $response->assertOk()
        ->assertJsonPath('data.content', fn (string $content) => str_contains($content, 'TL;DR'))
        ->assertJsonPath('data.content_en', fn (string $content) => str_contains($content, 'TL;DR'))
        ->assertJsonPath('data.authoritative_locale', 'nl')
        ->assertJsonStructure(['data' => ['content', 'content_en', 'authoritative_locale', 'updated_at']]);
});

it('reflects edits made through the PrivacyPolicy model', function () {
    PrivacyPolicy::current()->update(['content' => '<p>Updated policy text.</p>']);

    $response = $this->getJson('/api/v1/privacy-policy');

    $response->assertOk()
        ->assertJsonPath('data.content', '<p>Updated policy text.</p>');
});

it('reports content_en as null when no translation has been entered', function () {
    PrivacyPolicy::current()->update(['content_en' => null]);

    $response = $this->getJson('/api/v1/privacy-policy');

    $response->assertOk()
        ->assertJsonPath('data.content_en', null);
});
