<?php

use App\Filament\Pages\PrivacyPolicy as PrivacyPolicyPage;
use App\Mail\PrivacyPolicyUpdatedMail;
use App\Models\PrivacyPolicy;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

// Mirrors what `shield:generate --option=permissions` + the deploy sync step
// produce in production, without actually running the artisan command here.
function actingAsPrivacyPolicyAdmin(): User
{
    $admin = User::factory()->create([
        'email' => 'admin@example.com',
        'keycloak_id' => 'kc-privacy-admin',
    ]);

    $permission = Permission::firstOrCreate(['name' => 'View:PrivacyPolicy', 'guard_name' => 'web']);
    $admin->givePermissionTo($permission);

    filament()->setCurrentPanel(filament()->getPanel('admin'));
    test()->actingAs($admin);

    return $admin;
}

it('rejects users without the View:PrivacyPolicy permission', function () {
    $user = User::factory()->create([
        'email' => 'resident@example.com',
        'keycloak_id' => 'kc-privacy-resident',
    ]);

    filament()->setCurrentPanel(filament()->getPanel('admin'));
    test()->actingAs($user);

    expect(PrivacyPolicyPage::canAccess())->toBeFalse();
});

it('loads the current content into the form', function () {
    actingAsPrivacyPolicyAdmin();

    // The RichEditor's raw Livewire state is Tiptap JSON, not the stored
    // HTML (see RichEditorStateCast), and that JSON is only present in the
    // Livewire snapshot payload rather than as server-rendered DOM text -
    // assertSee() strips that payload, so check the raw response instead.
    $html = Livewire::test(PrivacyPolicyPage::class)
        ->assertOk()
        ->html();

    // No apostrophe in this fragment - the snapshot payload is an
    // HTML-attribute value, so a literal "'" would be entity-escaped there.
    expect($html)->toContain('TL;DR')
        ->toContain('Too Long');
});

it('saves without emailing anyone', function () {
    actingAsPrivacyPolicyAdmin();
    Mail::fake();

    Livewire::test(PrivacyPolicyPage::class)
        ->set('data.content', '<p>Plain save.</p>')
        ->call('save');

    expect(PrivacyPolicy::current()->content)->toBe('<p>Plain save.</p>');
    Mail::assertNothingSent();
});

it('saves and emails every activated resident on Save & Notify, skipping unactivated ones', function () {
    actingAsPrivacyPolicyAdmin();
    Mail::fake();

    $activated = User::factory()->create([
        'email' => 'activated@example.com',
        'keycloak_id' => 'kc-privacy-activated',
        'activated_at' => now(),
    ]);
    User::factory()->create([
        'email' => 'unactivated@example.com',
        'keycloak_id' => 'kc-privacy-unactivated',
    ]);

    Livewire::test(PrivacyPolicyPage::class)
        ->set('data.content', '<p>New terms.</p>')
        ->call('saveAndNotify');

    expect(PrivacyPolicy::current()->content)->toBe('<p>New terms.</p>');

    Mail::assertSent(PrivacyPolicyUpdatedMail::class, function (PrivacyPolicyUpdatedMail $mail) use ($activated) {
        return $mail->user->is($activated);
    });
    Mail::assertSentCount(1);
});
