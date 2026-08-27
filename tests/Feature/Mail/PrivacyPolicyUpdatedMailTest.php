<?php

use App\Mail\PrivacyPolicyUpdatedMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

it('sends in the user\'s locale', function () {
    Mail::fake();

    $user = User::factory()->create([
        'email' => 'nl-user@example.com',
        'keycloak_id' => 'kc-privacy-nl',
        'locale' => 'nl',
    ]);

    Mail::to($user)->send(new PrivacyPolicyUpdatedMail($user));

    Mail::assertSent(PrivacyPolicyUpdatedMail::class, function (PrivacyPolicyUpdatedMail $mail) {
        $rendered = $mail->render();

        return str_contains($rendered, 'privacybeleid')
            && str_contains($rendered, config('services.frontend_url').'/privacy-policy');
    });
});

it('falls back to english when the user prefers it', function () {
    Mail::fake();

    $user = User::factory()->create([
        'email' => 'en-user@example.com',
        'keycloak_id' => 'kc-privacy-en',
        'locale' => 'en',
    ]);

    Mail::to($user)->send(new PrivacyPolicyUpdatedMail($user));

    Mail::assertSent(PrivacyPolicyUpdatedMail::class, fn (PrivacyPolicyUpdatedMail $mail) => str_contains($mail->render(), 'privacy policy'));
});
