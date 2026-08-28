<?php

use App\Mail\AccountActivatedMail;
use App\Models\SentEmail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

// Deliberately not Mail::fake() here - LogSentEmail listens on Laravel's real
// MessageSent event, which a faked Mailer never dispatches. phpunit.xml sets
// MAIL_MAILER=array, so this still sends nothing over the network.
it('logs every mail sent, regardless of which Mailable sent it', function () {
    $user = User::factory()->create([
        'email' => 'logged@example.com',
        'keycloak_id' => 'kc-logged',
        'locale' => 'nl',
    ]);

    Mail::to($user)->send(new AccountActivatedMail($user));

    // Not just first(): LogSentEmail was previously wired up twice (once by
    // Laravel's event auto-discovery, once by an explicit Event::listen() in
    // AppServiceProvider), silently double-inserting a row per mail sent -
    // reported as duplicate entries in production. first() alone wouldn't
    // have caught that.
    expect(SentEmail::count())->toBe(1);

    $entry = SentEmail::first();

    expect($entry)->not->toBeNull()
        ->and($entry->to)->toBe('logged@example.com')
        ->and($entry->mailable)->toBe(AccountActivatedMail::class)
        ->and($entry->locale)->toBe('nl')
        ->and($entry->user_id)->toBe($user->id);
});
