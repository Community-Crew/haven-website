<?php

use App\Enums\MembershipStatus;
use App\Filament\Resources\Memberships\Pages\EditMembership;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Http\Middleware\EnsureUserAcceptedPrivacyPolicy;
use App\Http\Middleware\ValidateKeycloakToken;
use App\Listeners\GuardOutgoingMail;
use App\Mail\MembershipStatusChangedMail;
use App\Mail\ResidentMovedOutMail;
use App\Models\Membership;
use App\Models\MembershipDocument;
use App\Models\MemberType;
use App\Models\Unit;
use App\Models\User;
use App\Services\Keycloak\KeycloakAdminService;
use App\Services\ResidentOffboardingService;
use Database\Seeders\MemberDetailsSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\Mime\Email;

beforeEach(function () {
    Mail::fake();
    $this->withoutMiddleware([ValidateKeycloakToken::class, EnsureUserAcceptedPrivacyPolicy::class]);

    $this->user = User::factory()->create([
        'email' => 'member@example.com',
        'keycloak_id' => 'kc-member',
        'activated_at' => now(),
    ]);
    $this->actingAs($this->user);
});

function pendingMembership(User $user): Membership
{
    return Membership::create(['user_id' => $user->id, 'status' => MembershipStatus::PENDING]);
}

function publishDocuments(): array
{
    return [
        MembershipDocument::publish(MembershipDocument::STATUTES, '<p>Statuten v1</p>'),
        MembershipDocument::publish(MembershipDocument::RULES, '<p>HR v1</p>'),
    ];
}

function application(array $overrides = []): array
{
    [$statutes, $rules] = [MembershipDocument::current('statutes'), MembershipDocument::current('rules')];

    return [
        'phone' => '06 12345678',
        'birth_date' => '1999-04-12',
        'statutes_document_id' => $statutes?->id ?? 0,
        'rules_document_id' => $rules?->id ?? 0,
        'documents_accepted' => true,
        ...$overrides,
    ];
}

function boardMember(): User
{
    $board = User::factory()->create(['email' => 'board@example.com', 'keycloak_id' => 'kc-board']);
    $role = Role::firstOrCreate(['name' => 'Bestuur', 'guard_name' => 'web']);
    $role->givePermissionTo(collect(['ViewAny:User', 'View:User', 'Update:User', 'ViewAny:Membership', 'View:Membership', 'Update:Membership'])
        ->map(fn (string $name) => Permission::findOrCreate($name, 'web')));
    $board->assignRole($role);

    return $board;
}

it('stores the phone number in E.164, encrypted and outside the audit log', function () {
    $this->patchJson('/api/v1/user/contact-details', ['phone' => '06 12345678'])
        ->assertOk()
        ->assertJsonPath('data.phone', '+31612345678');

    $raw = DB::table('users')->where('id', $this->user->id)->value('phone');
    $log = Activity::all()->map(fn ($activity) => json_encode($activity->toArray()))->implode("\n");

    expect($raw)->not->toContain('612345678')
        ->and($log)->not->toContain('612345678');
});

it('rejects an invalid phone number', function () {
    $this->patchJson('/api/v1/user/contact-details', ['phone' => '123'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('phone');
});

it('only lets members change a birth date on their profile', function () {
    $this->patchJson('/api/v1/user/contact-details', ['birth_date' => '1999-04-12'])
        ->assertUnprocessable();

    pendingMembership($this->user);

    $this->patchJson('/api/v1/user/contact-details', ['birth_date' => '1999-04-12'])
        ->assertOk()
        ->assertJsonPath('data.birth_date', '1999-04-12');
});

it('locks the birth date once the board checked the ID', function () {
    $membership = pendingMembership($this->user);
    $this->user->update(['phone' => '+31612345678', 'birth_date' => '1999-04-12']);
    $membership->markIdentityVerified(boardMember());

    $this->patchJson('/api/v1/user/contact-details', ['birth_date' => '2000-01-01'])
        ->assertUnprocessable();

    expect($this->user->fresh()->birth_date)->toBe('1999-04-12');
});

it('requires a phone number to activate', function () {
    $this->user->update(['activated_at' => null]);
    $unit = Unit::create(['building' => 'Castor', 'floor' => '3', 'unit' => '2', 'max_residents' => 1]);
    $code = $unit->registrationCodes()->create([]);

    $this->postJson('/api/v1/user/activate', ['code' => $code->code])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('phone');

    $this->postJson('/api/v1/user/activate', ['code' => $code->code, 'phone' => '0612345678'])
        ->assertOk();

    expect($this->user->fresh()->phone)->toBe('+31612345678');
});

it('lets a resident apply and records the exact documents they accepted', function () {
    [$statutes, $rules] = publishDocuments();

    $this->getJson('/api/v1/membership-documents')
        ->assertOk()
        ->assertJsonPath('data.statutes.id', $statutes->id)
        ->assertJsonPath('data.rules.content', '<p>HR v1</p>');

    $this->postJson('/api/v1/user/membership-application', application())
        ->assertCreated()
        ->assertJsonPath('data.phone', '+31612345678')
        ->assertJsonPath('data.membership.status', 'pending')
        ->assertJsonPath('data.membership.documents_accepted', true)
        ->assertJsonPath('data.membership.identity_verified', false);

    $membership = Membership::where('user_id', $this->user->id)->sole();

    expect($membership->member_type_id)->toBeNull()
        ->and($membership->statutes_document_id)->toBe($statutes->id)
        ->and($membership->rules_document_id)->toBe($rules->id)
        ->and($membership->documents_accepted_at)->not->toBeNull()
        ->and($membership->canBeActivated())->toBeFalse();
});

it('needs the documents to be accepted', function () {
    publishDocuments();

    $this->postJson('/api/v1/user/membership-application', application(['documents_accepted' => false]))
        ->assertUnprocessable();

    expect(Membership::count())->toBe(0);
});

it('refuses an application for an outdated version', function () {
    publishDocuments();
    $old = application();
    MembershipDocument::publish(MembershipDocument::RULES, '<p>HR v2</p>');

    $this->postJson('/api/v1/user/membership-application', $old)
        ->assertConflict()
        ->assertJsonPath('errors', 'DOCUMENTS_OUTDATED');

    expect(Membership::count())->toBe(0);
});

it('keeps applications closed until both documents exist', function () {
    MembershipDocument::publish(MembershipDocument::STATUTES, '<p>Statuten v1</p>');

    $this->postJson('/api/v1/user/membership-application', application(['rules_document_id' => 1]))
        ->assertConflict()
        ->assertJsonPath('errors', 'APPLICATIONS_CLOSED');
});

it('adds a version only when the text changed', function () {
    $first = MembershipDocument::publish(MembershipDocument::STATUTES, '<p>A</p>');

    expect(MembershipDocument::publish(MembershipDocument::STATUTES, '<p>A</p>')->id)->toBe($first->id);

    $second = MembershipDocument::publish(MembershipDocument::STATUTES, '<p>B</p>');

    expect($second->id)->not->toBe($first->id)
        ->and($first->fresh()->content)->toBe('<p>A</p>');
});

it('does not allow a second application', function () {
    publishDocuments();
    $this->postJson('/api/v1/user/membership-application', application())->assertCreated();

    $this->postJson('/api/v1/user/membership-application', application())
        ->assertConflict()
        ->assertJsonPath('errors', 'MEMBERSHIP_ALREADY_OPEN');

    expect(Membership::where('user_id', $this->user->id)->count())->toBe(1);
});

it('completes a pending membership the board created', function () {
    publishDocuments();
    $membership = pendingMembership($this->user);

    $this->postJson('/api/v1/user/membership-application', application())->assertCreated();

    expect(Membership::where('user_id', $this->user->id)->sole()->id)->toBe($membership->id)
        ->and($membership->fresh()->documents_accepted_at)->not->toBeNull();
});

function address(): array
{
    return ['line' => 'Voorbeeldstraat 1', 'postal_code' => '1234 ab', 'city' => 'Eindhoven', 'country' => 'nl'];
}

function beneficiary(User $user, MembershipStatus $status = MembershipStatus::ACTIVE): Membership
{
    $user->update([
        'activated_at' => null,
        'moved_out_at' => now()->subYear(),
        'phone' => '+31612345678',
        'birth_date' => '1999-04-12',
        'postal_address' => ['line' => 'Voorbeeldstraat 1', 'postal_code' => '1234 AB', 'city' => 'Eindhoven', 'country' => 'NL'],
    ]);

    return Membership::create([
        'user_id' => $user->id,
        'member_type_id' => MemberType::where('is_beneficiary', true)->sole()->id,
        'status' => $status,
    ]);
}

it('makes a former resident who applies a beneficiary', function () {
    publishDocuments();
    $this->user->update(['activated_at' => null, 'moved_out_at' => now()]);

    $this->postJson('/api/v1/user/membership-application', application(['postal_address' => address()]))
        ->assertCreated()
        ->assertJsonPath('data.is_former_resident', true)
        ->assertJsonPath('data.membership.is_beneficiary', true)
        ->assertJsonPath('data.postal_address.postal_code', '1234 AB')
        ->assertJsonPath('data.anonymizes_at', null);

    $membership = Membership::where('user_id', $this->user->id)->sole();

    expect($membership->status)->toBe(MembershipStatus::PENDING)
        ->and($membership->memberType->is_beneficiary)->toBeTrue()
        ->and($membership->has_voting_rights)->toBeFalse();

    expect(DB::table('users')->where('id', $this->user->id)->value('postal_address'))->not->toContain('Voorbeeldstraat');
});

it('needs a postal address from a former resident, and refuses one from a resident', function () {
    publishDocuments();

    $this->postJson('/api/v1/user/membership-application', application(['postal_address' => address()]))
        ->assertUnprocessable();

    $this->user->update(['activated_at' => null, 'moved_out_at' => now()]);

    $this->postJson('/api/v1/user/membership-application', application())
        ->assertUnprocessable()
        ->assertJsonValidationErrors('postal_address');
});

it('keeps former residents away from resident features', function () {
    $this->user->update(['activated_at' => null, 'moved_out_at' => now()]);

    $this->getJson('/api/v1/membership-documents')->assertOk();
    $this->getJson('/api/v1/reservations/me')->assertForbidden();
});

it('does not anonymize an open beneficiary', function () {
    Http::fake();
    $membership = beneficiary($this->user);

    $this->artisan('app:anonymize-former-residents')->assertSuccessful();

    expect($this->user->fresh()->anonymized_at)->toBeNull()
        ->and($this->user->fresh()->phone)->toBe('+31612345678')
        ->and($membership->fresh()->status)->toBe(MembershipStatus::ACTIVE);
});

it('restarts the retention period when a beneficiary ends', function () {
    Http::fake();
    $membership = beneficiary($this->user);

    $membership->update(['status' => MembershipStatus::ENDED]);

    $this->artisan('app:anonymize-former-residents')->assertSuccessful();

    expect($this->user->fresh()->anonymized_at)->toBeNull()
        ->and($this->user->fresh()->moved_out_at->isToday())->toBeTrue();

    Mail::assertSent(ResidentMovedOutMail::class);
});

it('does not restart the retention period for a rejected application', function () {
    $this->mock(KeycloakAdminService::class)->shouldReceive('deleteUser')->once()->with('kc-member');
    $membership = beneficiary($this->user, MembershipStatus::PENDING);

    $membership->update(['status' => MembershipStatus::ENDED]);
    $this->artisan('app:anonymize-former-residents')->assertSuccessful();

    expect($this->user->fresh()->anonymized_at)->not->toBeNull()
        ->and($this->user->fresh()->postal_address)->toBeNull();
});

it('ends an ordinary membership and unlinks the tag at move-out', function () {
    $type = MemberType::create(['name' => 'Gewoon lid']);
    $membership = Membership::create(['user_id' => $this->user->id, 'member_type_id' => $type->id, 'status' => MembershipStatus::ACTIVE]);
    $this->user->update(['access_tag_hash' => User::hashAccessTag('04A1B2C3')]);

    app(ResidentOffboardingService::class)->markMovedOut($this->user->fresh());

    expect($membership->fresh()->status)->toBe(MembershipStatus::ENDED)
        ->and($this->user->fresh()->access_tag_hash)->toBeNull();
});

it('ends the beneficiary membership when they become a resident again', function () {
    $membership = beneficiary($this->user);
    $unit = Unit::create(['building' => 'Castor', 'floor' => '3', 'unit' => '2', 'max_residents' => 1]);
    $code = $unit->registrationCodes()->create([]);

    $this->actingAs($this->user->fresh())
        ->postJson('/api/v1/user/activate', ['code' => $code->code])
        ->assertOk();

    expect($membership->fresh()->status)->toBe(MembershipStatus::ENDED)
        ->and($this->user->fresh()->moved_out_at)->toBeNull()
        ->and($this->user->fresh()->postal_address)->toBeNull();
});

it('lets status mail through to beneficiaries, but not to other former residents', function () {
    beneficiary($this->user);
    $other = User::factory()->create(['email' => 'gone@example.com', 'keycloak_id' => 'kc-gone', 'moved_out_at' => now()]);
    $guard = app(GuardOutgoingMail::class);
    $sending = fn (string $to) => new MessageSending((new Email)->to($to), ['__laravel_mailable' => MembershipStatusChangedMail::class]);

    expect($guard->handle($sending($this->user->email)))->not->toBeFalse()
        ->and($guard->handle($sending($other->email)))->toBeFalse();
});

it('only offers the beneficiary type when approving a former resident', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    publishDocuments();
    $this->user->update(['activated_at' => null, 'moved_out_at' => now()]);
    $this->postJson('/api/v1/user/membership-application', application(['postal_address' => address()]))->assertCreated();
    $regular = MemberType::create(['name' => 'Gewoon lid']);
    $beneficiaryType = MemberType::where('is_beneficiary', true)->sole();
    $this->actingAs(boardMember());

    Livewire::test(ListUsers::class)
        ->callAction(TestAction::make('approveMembership')->table($this->user), ['member_type_id' => $regular->id, 'id_checked' => true]);

    expect(Membership::where('user_id', $this->user->id)->sole()->status)->toBe(MembershipStatus::PENDING);

    Livewire::test(ListUsers::class)
        ->callAction(TestAction::make('approveMembership')->table($this->user), ['member_type_id' => $beneficiaryType->id, 'id_checked' => true]);

    expect(Membership::where('user_id', $this->user->id)->sole())
        ->status->toBe(MembershipStatus::ACTIVE)
        ->member_type_id->toBe($beneficiaryType->id);
});

it('lets the board appoint an erelid, converting an open membership', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $membership = pendingMembership($this->user);
    $honoraryType = MemberType::where('is_honorary', true)->sole();
    $board = boardMember();
    $this->actingAs($board);

    Livewire::test(ListUsers::class)
        ->callAction(TestAction::make('appointHonorary')->table($this->user), [
            'member_type_id' => $honoraryType->id,
            'id_checked' => true,
        ])
        ->assertHasNoFormErrors();

    expect(Membership::where('user_id', $this->user->id)->sole())
        ->id->toBe($membership->id)
        ->status->toBe(MembershipStatus::ACTIVE)
        ->member_type_id->toBe($honoraryType->id)
        ->identity_verified_by->toBe($board->id);
});

it('keeps an erelid after move-out, without the removal notice', function () {
    Http::fake();
    $membership = Membership::create([
        'user_id' => $this->user->id,
        'member_type_id' => MemberType::where('is_honorary', true)->sole()->id,
        'status' => MembershipStatus::ACTIVE,
    ]);
    $offboarding = app(ResidentOffboardingService::class);

    $offboarding->markMovedOut($this->user->fresh());
    $offboarding->sendMovedOutNotice($this->user->fresh());
    $this->user->fresh()->forceFill(['moved_out_at' => now()->subYear()])->saveQuietly();
    $this->artisan('app:anonymize-former-residents')->assertSuccessful();

    expect($membership->fresh()->status)->toBe(MembershipStatus::ACTIVE)
        ->and($this->user->fresh()->anonymized_at)->toBeNull();

    Mail::assertNotSent(ResidentMovedOutMail::class);
});

it('restarts the retention period when an erelid ends after moving out', function () {
    Http::fake();
    $this->user->update(['activated_at' => null, 'moved_out_at' => now()->subYear()]);
    $membership = Membership::create([
        'user_id' => $this->user->id,
        'member_type_id' => MemberType::where('is_honorary', true)->sole()->id,
        'status' => MembershipStatus::ACTIVE,
    ]);

    $membership->update(['status' => MembershipStatus::ENDED]);
    $this->artisan('app:anonymize-former-residents')->assertSuccessful();

    expect($this->user->fresh()->anonymized_at)->toBeNull()
        ->and($this->user->fresh()->moved_out_at->isToday())->toBeTrue();

    Mail::assertSent(ResidentMovedOutMail::class);
});

it('keeps an erelid when they activate on a new unit', function () {
    $this->user->update(['activated_at' => null, 'moved_out_at' => now(), 'phone' => '+31612345678']);
    $membership = Membership::create([
        'user_id' => $this->user->id,
        'member_type_id' => MemberType::where('is_honorary', true)->sole()->id,
        'status' => MembershipStatus::ACTIVE,
    ]);
    $unit = Unit::create(['building' => 'Castor', 'floor' => '3', 'unit' => '2', 'max_residents' => 1]);
    $code = $unit->registrationCodes()->create([]);

    $this->actingAs($this->user->fresh())
        ->postJson('/api/v1/user/activate', ['code' => $code->code])
        ->assertOk();

    expect($membership->fresh()->status)->toBe(MembershipStatus::ACTIVE);
});

it('never offers the honorary type when approving an application', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    publishDocuments();
    $this->postJson('/api/v1/user/membership-application', application())->assertCreated();
    $honoraryType = MemberType::where('is_honorary', true)->sole();
    $this->actingAs(boardMember());

    Livewire::test(ListUsers::class)
        ->callAction(TestAction::make('approveMembership')->table($this->user), [
            'member_type_id' => $honoraryType->id,
            'id_checked' => true,
        ]);

    expect(Membership::where('user_id', $this->user->id)->sole()->status)->toBe(MembershipStatus::PENDING);
});

it('hides anonymized accounts and filters members by kind', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Membership::create([
        'user_id' => $this->user->id,
        'member_type_id' => MemberType::where('is_honorary', true)->sole()->id,
        'status' => MembershipStatus::ACTIVE,
    ]);
    $anonymized = User::factory()->create([
        'email' => 'gone@example.com', 'keycloak_id' => 'kc-gone', 'moved_out_at' => now(), 'anonymized_at' => now(),
    ]);
    $this->actingAs(boardMember());

    Livewire::test(ListUsers::class)
        ->assertCanNotSeeTableRecords([$anonymized])
        ->assertTableColumnStateSet('status', 'Erelid', $this->user)
        ->filterTable('anonymized', 'only')
        ->assertCanSeeTableRecords([$anonymized])
        ->assertCanNotSeeTableRecords([$this->user]);

    Livewire::test(ListUsers::class)
        ->set('activeTab', 'members')
        ->filterTable('kind', 'honorary')
        ->assertCanSeeTableRecords([$this->user])
        ->filterTable('kind', 'beneficiary')
        ->assertCanNotSeeTableRecords([$this->user]);
});

it('shows the next step and puts ready applications first', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    publishDocuments();
    pendingMembership($this->user);
    $ready = User::factory()->create(['email' => 'ready@example.com', 'keycloak_id' => 'kc-ready', 'activated_at' => now()]);
    $this->actingAs($ready);
    $this->postJson('/api/v1/user/membership-application', application())->assertCreated();
    $this->actingAs(boardMember());

    Livewire::test(ListUsers::class)
        ->set('activeTab', 'applications')
        ->assertCanSeeTableRecords([$ready, $this->user], inOrder: true)
        ->assertTableColumnVisible('next_step')
        ->assertTableColumnStateSet('next_step', 'Ready for ID check', $ready)
        ->assertTableColumnStateSet('next_step', 'Waiting for member', $this->user)
        ->assertActionVisible(TestAction::make('approveMembership')->table($ready))
        ->assertActionHidden(TestAction::make('approveMembership')->table($this->user))
        ->assertActionVisible(TestAction::make('rejectMembership')->table($this->user));

    expect(Membership::readyForReview()->count())->toBe(1);
});

it('lets the board link a tag without touching other details', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs(boardMember());
    $this->user->update(['phone' => '+31612345678', 'birth_date' => '1999-04-12']);

    Livewire::test(EditUser::class, ['record' => $this->user->getRouteKey()])
        ->fillForm(['access_tag_hash' => '04:a1:b2:c3'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->user->fresh())
        ->access_tag_hash->toBe(User::hashAccessTag('04A1B2C3'))
        ->phone->toBe('+31612345678')
        ->birth_date->toBe('1999-04-12');
});

it('has the board approve an application on the members page', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    publishDocuments();
    $this->postJson('/api/v1/user/membership-application', application())->assertCreated();
    $membership = Membership::where('user_id', $this->user->id)->sole();
    $type = MemberType::create(['name' => 'Gewoon lid', 'has_voting_rights' => false]);
    $board = boardMember();
    $this->actingAs($board);

    Livewire::test(EditMembership::class, ['record' => $membership->getRouteKey()])
        ->fillForm(['status' => 'active'])
        ->call('save')
        ->assertHasFormErrors(['status']);

    Livewire::test(ListUsers::class)
        ->set('activeTab', 'applications')
        ->assertCanSeeTableRecords([$this->user])
        ->callAction(TestAction::make('approveMembership')->table($this->user), [
            'member_type_id' => $type->id,
            'id_checked' => true,
        ])
        ->assertHasNoFormErrors();

    expect($membership->fresh())
        ->status->toBe(MembershipStatus::ACTIVE)
        ->member_type_id->toBe($type->id)
        ->has_voting_rights->toBeFalse()
        ->identity_verified_by->toBe($board->id)
        ->joined_at->not->toBeNull();

    Mail::assertSent(MembershipStatusChangedMail::class);
});

it('needs the ID check confirmed to approve', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    publishDocuments();
    $this->postJson('/api/v1/user/membership-application', application())->assertCreated();
    $type = MemberType::create(['name' => 'Gewoon lid']);
    $this->actingAs(boardMember());

    Livewire::test(ListUsers::class)
        ->callAction(TestAction::make('approveMembership')->table($this->user), [
            'member_type_id' => $type->id,
            'id_checked' => false,
        ])
        ->assertHasFormErrors(['id_checked']);

    expect(Membership::where('user_id', $this->user->id)->sole()->status)->toBe(MembershipStatus::PENDING);
});

it('lets the board reject an application', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    publishDocuments();
    $this->postJson('/api/v1/user/membership-application', application())->assertCreated();
    $this->actingAs(boardMember());

    Livewire::test(ListUsers::class)
        ->callAction(TestAction::make('rejectMembership')->table($this->user));

    expect(Membership::where('user_id', $this->user->id)->sole()->status)->toBe(MembershipStatus::ENDED);
});

it('does not let a board member review their own application', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $board = boardMember();
    pendingMembership($board);
    $this->actingAs($board);

    Livewire::test(ListUsers::class)
        ->assertActionHidden(TestAction::make('approveMembership')->table($board))
        ->assertActionHidden(TestAction::make('rejectMembership')->table($board));
});

it('seeds every step of the membership flow and can run again', function () {
    $this->seed(MemberDetailsSeeder::class);
    $this->seed(MemberDetailsSeeder::class);

    $seeded = fn (string $key) => User::where('keycloak_id', "seed-{$key}")->first();

    expect(User::where('keycloak_id', 'like', 'seed-%')->count())->toBe(10)
        ->and(Membership::count())->toBe(7)
        ->and(MembershipDocument::count())->toBe(2)
        ->and($seeded('member-needs-id-check')->currentMembership->documents_accepted_at)->not->toBeNull()
        ->and($seeded('member-active')->currentMembership->canBeActivated())->toBeTrue()
        ->and($seeded('member-active')->access_tag_hash)->toBe(User::hashAccessTag('SEED0001'))
        ->and($this->user->fresh()->name)->toBe($this->user->name);

    Mail::assertNothingSent();
});

it('refuses to seed test members outside local and testing', function () {
    app()->detectEnvironment(fn () => 'production');

    (new MemberDetailsSeeder)->run();

    expect(User::where('keycloak_id', 'like', 'seed-%')->count())->toBe(0);
});

it('hashes a tag the same way whatever the reader format', function () {
    expect(User::hashAccessTag('04:a1:b2:c3'))->toBe(User::hashAccessTag('04A1B2C3'))
        ->and(User::hashAccessTag('04A1B2C3'))->not->toContain('04A1B2C3');
});
