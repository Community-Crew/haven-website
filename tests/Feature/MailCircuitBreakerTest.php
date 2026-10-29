<?php

use App\Mail\AccountActivatedMail;
use App\Mail\PrivacyPolicyUpdatedMail;
use App\Models\SentEmail;
use App\Models\User;
use App\Support\MailCircuitBreaker;
use Illuminate\Support\Facades\Mail;

// Deliberately not Mail::fake(): the fake skips Laravel's MessageSending
// event, which is exactly what the breaker hooks into. phpunit.xml's 'array'
// mailer sends for real (in memory) and fires every event.

beforeEach(function () {
    config([
        'mail.breaker.max_per_window' => 3,
        'mail.breaker.max_per_recipient_per_hour' => 2,
        'mail.breaker.alert_to' => 'alerts@example.com',
    ]);
});

function breakerUser(): User
{
    return User::factory()->create([
        'email' => 'breaker-'.uniqid().'@example.com',
        'keycloak_id' => 'kc-breaker-'.uniqid(),
        'locale' => 'en',
    ]);
}

function sentMessages(): array
{
    return collect(Mail::mailer('array')->getSymfonyTransport()->messages())
        ->map(fn ($sent) => [
            'to' => $sent->getOriginalMessage()->getTo()[0]->getAddress(),
            'subject' => $sent->getOriginalMessage()->getSubject(),
        ])
        ->all();
}

it('lets normal mail through', function () {
    $user = breakerUser();

    Mail::to($user)->send(new AccountActivatedMail($user));

    expect(SentEmail::count())->toBe(1);
});

it('trips after too many mails in the window, drops the rest and alerts once', function () {
    $users = collect(range(1, 6))->map(fn () => breakerUser());

    $users->each(fn (User $user) => Mail::to($user)->send(new AccountActivatedMail($user)));

    // The alert itself is logged to sent_emails too, like any other mail.
    expect(SentEmail::where('to', '!=', 'alerts@example.com')->count())->toBe(3)
        ->and(app(MailCircuitBreaker::class)->trippedAt())->not->toBeNull();

    $alerts = collect(sentMessages())->where('to', 'alerts@example.com');
    expect($alerts)->toHaveCount(1)
        ->and($alerts->first()['subject'])->toContain('circuit breaker');
});

it('keeps blocking after the window passes, until the cooldown ends', function () {
    collect(range(1, 4))->each(function () {
        $user = breakerUser();
        Mail::to($user)->send(new AccountActivatedMail($user));
    });

    $this->travel(30)->minutes();
    $late = breakerUser();
    Mail::to($late)->send(new AccountActivatedMail($late));
    expect(SentEmail::where('to', $late->email)->exists())->toBeFalse();

    $this->travel(config('mail.breaker.cooldown_hours'))->hours();
    Mail::to($late)->send(new AccountActivatedMail($late));
    expect(SentEmail::where('to', $late->email)->exists())->toBeTrue();
});

it('resumes sending after a manual reset', function () {
    collect(range(1, 4))->each(function () {
        $user = breakerUser();
        Mail::to($user)->send(new AccountActivatedMail($user));
    });
    $this->travel(11)->minutes();

    $this->artisan('app:mail-breaker')->expectsOutputToContain('Tripped');
    $this->artisan('app:mail-breaker --reset')->assertSuccessful();

    $user = breakerUser();
    Mail::to($user)->send(new AccountActivatedMail($user));

    expect(SentEmail::where('to', $user->email)->exists())->toBeTrue();
});

it('drops mail over the per-recipient limit without tripping', function () {
    config(['mail.breaker.max_per_window' => 100]);
    $user = breakerUser();

    collect(range(1, 4))->each(fn () => Mail::to($user)->send(new AccountActivatedMail($user)));

    expect(SentEmail::where('to', $user->email)->count())->toBe(2)
        ->and(app(MailCircuitBreaker::class)->trippedAt())->toBeNull();
});

it('lets deliberate bulk mail past the global limit without counting it', function () {
    $users = collect(range(1, 6))->map(fn () => breakerUser());

    $users->each(fn (User $user) => Mail::to($user)->send(new PrivacyPolicyUpdatedMail($user)));

    expect(SentEmail::count())->toBe(6)
        ->and(app(MailCircuitBreaker::class)->trippedAt())->toBeNull();

    // A normal mail right after the bulk send still goes out.
    $user = breakerUser();
    Mail::to($user)->send(new AccountActivatedMail($user));
    expect(SentEmail::where('to', $user->email)->exists())->toBeTrue();
});

it('does nothing when disabled', function () {
    config(['mail.breaker.enabled' => false]);

    collect(range(1, 5))->each(function () {
        $user = breakerUser();
        Mail::to($user)->send(new AccountActivatedMail($user));
    });

    expect(SentEmail::count())->toBe(5);
});
