<?php

use App\Enums\CheckVerificationStatus;
use App\Enums\MembershipStatus;
use App\Http\Middleware\ValidateKeycloakToken;
use App\Mail\ReservationConfirmedMail;
use App\Mail\ResidentMovedOutMail;
use App\Models\Media;
use App\Models\Membership;
use App\Models\MemberType;
use App\Models\Reservation;
use App\Models\ReservationCheck;
use App\Models\ReservationNote;
use App\Models\Room;
use App\Models\SentEmail;
use App\Models\Unit;
use App\Models\User;
use App\Services\ResidentOffboardingService;
use App\Services\VestideMutatieImporter;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;

// Real 'array' mailer, not Mail::fake(): the former-resident guard runs on
// Laravel's MessageSending event, which the fake skips.

beforeEach(function () {
    // Switchable per test - a second Http::fake() call can't override a
    // catch-all stub registered here, since earlier stubs win.
    $this->keycloakStatus = 204;
    Http::fake(fn (Request $request) => str_contains($request->url(), '/protocol/openid-connect/token')
        ? Http::response(['access_token' => 'fake-admin-token'])
        : Http::response(null, $this->keycloakStatus));
    Storage::fake('hetzner');

    $this->unit = Unit::create(['building' => 'Pollux', 'floor' => '7', 'unit' => '9', 'subunit' => 'D', 'max_residents' => 1]);
    $this->room = Room::create(['name' => 'Werkplaats', 'slug' => 'werkplaats-offboarding', 'description' => 'A room', 'location' => 'Ground floor', 'status' => 'available']);

    $this->resident = User::factory()->create([
        'name' => 'Sanne de Vries',
        'email' => 'sanne@example.com',
        'keycloak_id' => 'kc-sanne',
        'locale' => 'nl',
        'unit_id' => $this->unit->id,
        'activated_at' => now()->subYear(),
    ]);

    $this->offboarding = app(ResidentOffboardingService::class);
});

function sentTo(string $email): array
{
    return SentEmail::where('to', $email)->pluck('mailable')->all();
}

it('marks a resident as moved out, frees their unit and ends open memberships', function () {
    $type = MemberType::create(['name' => 'Regular']);
    $membership = Membership::create(['user_id' => $this->resident->id, 'member_type_id' => $type->id, 'status' => 'active']);

    expect($this->offboarding->markMovedOut($this->resident))->toBeTrue();

    $this->resident->refresh();
    expect($this->resident->moved_out_at)->not->toBeNull()
        ->and($this->resident->unit_id)->toBeNull()
        ->and($this->resident->activated_at)->toBeNull()
        ->and($this->resident->isFormerResident())->toBeTrue()
        ->and($membership->fresh()->status)->toBe(MembershipStatus::ENDED)
        // The membership-ended mail is dropped - they're a former resident by then.
        ->and(sentTo('sanne@example.com'))->toBe([]);

    expect($this->offboarding->markMovedOut($this->resident))->toBeFalse();
});

it('emails the move-out notice in their language, with the deletion date and a reply-to', function () {
    config(['mail.contact_address' => 'bestuur@example.com']);
    $this->travelTo('2026-11-02 10:00');

    $this->offboarding->markMovedOut($this->resident);
    $this->offboarding->sendMovedOutNotice($this->resident->fresh());

    expect(sentTo('sanne@example.com'))->toBe([ResidentMovedOutMail::class]);

    $message = collect(Mail::mailer('array')->getSymfonyTransport()->messages())->last()->getOriginalMessage();
    expect($message->getSubject())->toBe('Je Haven-account wordt verwijderd')
        ->and($message->getReplyTo()[0]->getAddress())->toBe('bestuur@example.com')
        ->and($message->getTextBody())->toContain('2 december 2026');
});

it('drops any other mail to a former resident', function () {
    $this->offboarding->markMovedOut($this->resident);

    $reservation = Reservation::create([
        'name' => 'Borrel', 'start_at' => now()->subDays(3), 'end_at' => now()->subDays(3)->addHours(2),
        'status' => 'approved', 'user_id' => $this->resident->id, 'room_id' => $this->room->id,
    ]);
    Mail::to($this->resident)->send(new ReservationConfirmedMail($reservation));

    expect(sentTo('sanne@example.com'))->toBe([]);
});

it('can undo a move-out before anonymization', function () {
    $this->offboarding->markMovedOut($this->resident);
    $this->offboarding->undoMoveOut($this->resident);

    expect($this->resident->fresh()->moved_out_at)->toBeNull()
        ->and($this->resident->fresh()->isFormerResident())->toBeFalse();
});

it('anonymizes everything personal but keeps reservations for statistics', function () {
    $reservation = Reservation::create([
        'name' => 'Sanne\'s verjaardag', 'start_at' => now()->subMonths(2), 'end_at' => now()->subMonths(2)->addHours(3),
        'status' => 'approved', 'user_id' => $this->resident->id, 'room_id' => $this->room->id, 'share_user' => true,
    ]);
    $check = ReservationCheck::create([
        'reservation_id' => $reservation->id, 'type' => 'check_out', 'user_id' => $this->resident->id,
        'occurred_at' => $reservation->end_at, 'verification_status' => CheckVerificationStatus::VERIFIED,
    ]);
    $photo = Media::create([
        'disk' => 'hetzner', 'path' => 'media/'.ReservationCheck::PHOTO_COLLECTION.'/sanne.webp',
        'collection' => ReservationCheck::PHOTO_COLLECTION,
        'mediable_type' => $check->getMorphClass(), 'mediable_id' => $check->id,
    ]);
    Storage::disk('hetzner')->put($photo->path, 'bytes');
    ReservationNote::create(['reservation_id' => $reservation->id, 'body' => 'Sanne left the key behind.']);
    Mail::to($this->resident)->send(new ReservationConfirmedMail($reservation));

    $this->offboarding->markMovedOut($this->resident);
    $this->offboarding->anonymize($this->resident->fresh());

    $user = $this->resident->fresh();
    expect($user->name)->toBe("Former resident #{$user->id}")
        ->and($user->email)->toEndWith('@anonymized.invalid')
        ->and($user->keycloak_id)->toBe("anonymized-{$user->id}")
        ->and($user->anonymized_at)->not->toBeNull();

    expect(Reservation::find($reservation->id))->not->toBeNull()
        ->and(Reservation::find($reservation->id)->name)->toBeNull()
        ->and(Reservation::find($reservation->id)->share_user)->toBeFalse()
        ->and(ReservationCheck::find($check->id))->not->toBeNull()
        ->and(Media::find($photo->id))->toBeNull()
        ->and(Storage::disk('hetzner')->exists($photo->path))->toBeFalse()
        ->and(ReservationNote::count())->toBe(0)
        ->and(SentEmail::where('to', 'sanne@example.com')->exists())->toBeFalse();

    // Nothing in the audit log still mentions them - including the
    // anonymizing update itself.
    $log = Activity::all()->map(fn ($activity) => json_encode($activity->toArray()))->implode("\n");
    expect($log)->not->toContain('sanne@example.com')
        ->and($log)->not->toContain('Sanne');

    Http::assertSent(fn (Request $request) => $request->method() === 'DELETE' && str_ends_with($request->url(), '/users/kc-sanne'));
});

it('changes nothing when deleting the Keycloak account fails', function () {
    $this->keycloakStatus = 500;
    $this->offboarding->markMovedOut($this->resident);

    expect(fn () => $this->offboarding->anonymize($this->resident->fresh()))->toThrow(RequestException::class);

    expect($this->resident->fresh()->name)->toBe('Sanne de Vries')
        ->and($this->resident->fresh()->anonymized_at)->toBeNull();
});

it('anonymizes only residents whose retention period is over', function () {
    $this->offboarding->markMovedOut($this->resident);

    $recent = User::factory()->create(['email' => 'recent@example.com', 'keycloak_id' => 'kc-recent', 'locale' => 'en', 'activated_at' => now()->subYear()]);
    $this->offboarding->markMovedOut($recent);

    $this->resident->forceFill(['moved_out_at' => now()->subDays(ResidentOffboardingService::RETENTION_DAYS + 1)])->saveQuietly();

    $this->artisan('app:anonymize-former-residents')->assertSuccessful();

    expect($this->resident->fresh()->anonymized_at)->not->toBeNull()
        ->and($recent->fresh()->anonymized_at)->toBeNull();
});

it('marks a unit\'s residents as moved out and emails them when a Vestide row is applied', function () {
    $rows = app(VestideMutatieImporter::class)->apply([[
        'unit_id' => $this->unit->id,
        'skip' => false,
        'applied' => false,
    ]]);

    expect($rows[0]['applied'])->toBeTrue()
        ->and($this->resident->fresh()->moved_out_at)->not->toBeNull()
        ->and(sentTo('sanne@example.com'))->toBe([ResidentMovedOutMail::class]);
});

it('cancels a move-out when they activate on another unit', function () {
    $this->offboarding->markMovedOut($this->resident);
    $newUnit = Unit::create(['building' => 'Castor', 'floor' => '3', 'unit' => '2', 'max_residents' => 1]);
    $code = $newUnit->registrationCodes()->create([]);

    $this->withoutMiddleware([ValidateKeycloakToken::class]);

    $response = $this->actingAs($this->resident->fresh())
        ->postJson('/api/v1/user/activate', ['code' => $code->code]);

    expect($response->status())->toBe(200)
        ->and($this->resident->fresh()->moved_out_at)->toBeNull()
        ->and($this->resident->fresh()->unit_id)->toBe($newUnit->id);
});

it('lists deactivated past residents and long-inactive ones for review', function () {
    $clearedByImport = User::factory()->create(['email' => 'cleared@example.com', 'keycloak_id' => 'kc-cleared', 'activated_at' => null]);
    Reservation::create([
        'name' => 'Old', 'start_at' => now()->subYear(), 'end_at' => now()->subYear()->addHour(),
        'status' => 'approved', 'user_id' => $clearedByImport->id, 'room_id' => $this->room->id,
    ]);

    $neverActivated = User::factory()->create(['email' => 'new@example.com', 'keycloak_id' => 'kc-new', 'activated_at' => null]);
    $active = User::factory()->create(['email' => 'active@example.com', 'keycloak_id' => 'kc-active', 'activated_at' => now()->subYear()]);
    Reservation::create([
        'name' => 'Recent', 'start_at' => now()->subWeek(), 'end_at' => now()->subWeek()->addHour(),
        'status' => 'approved', 'user_id' => $active->id, 'room_id' => $this->room->id,
    ]);

    // $this->resident: activated a year ago, no reservations or logins since.
    $ids = User::possiblyMovedOut()->pluck('id');

    expect($ids)->toContain($clearedByImport->id)
        ->and($ids)->toContain($this->resident->id)
        ->and($ids)->not->toContain($neverActivated->id)
        ->and($ids)->not->toContain($active->id);

    $this->offboarding->markMovedOut($this->resident);
    expect(User::possiblyMovedOut()->pluck('id'))->not->toContain($this->resident->id);
});
