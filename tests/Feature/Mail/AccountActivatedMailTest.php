<?php

use App\Mail\AccountActivatedMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

it('sends in the user\'s locale', function () {
    Mail::fake();

    $user = User::factory()->create([
        'email' => 'nl-user@example.com',
        'keycloak_id' => 'kc-1',
        'locale' => 'nl',
    ]);

    Mail::to($user)->send(new AccountActivatedMail($user));

    Mail::assertSent(AccountActivatedMail::class, function (AccountActivatedMail $mail) {
        $rendered = $mail->render();

        return str_contains($rendered, 'geactiveerd')
            && ! str_contains($rendered, 'activated');
    });
});

it('falls back to english when the user prefers it', function () {
    Mail::fake();

    $user = User::factory()->create([
        'email' => 'en-user@example.com',
        'keycloak_id' => 'kc-2',
        'locale' => 'en',
    ]);

    Mail::to($user)->send(new AccountActivatedMail($user));

    Mail::assertSent(AccountActivatedMail::class, fn (AccountActivatedMail $mail) => str_contains($mail->render(), 'activated'));
});
