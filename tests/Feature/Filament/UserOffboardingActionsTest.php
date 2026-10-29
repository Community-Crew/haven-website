<?php

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Mail\ResidentMovedOutMail;
use App\Models\SentEmail;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

function actingAsUserAdmin(array $permissions): User
{
    $admin = User::factory()->create(['email' => 'user-admin-'.uniqid().'@example.com', 'keycloak_id' => 'kc-user-admin-'.uniqid(), 'locale' => 'en']);

    foreach ($permissions as $name) {
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
    }

    filament()->setCurrentPanel(filament()->getPanel('admin'));
    test()->actingAs($admin);

    return $admin;
}

beforeEach(function () {
    Http::fake([
        '*/protocol/openid-connect/token' => Http::response(['access_token' => 'fake-admin-token']),
        '*' => Http::response(),
    ]);

    $this->resident = User::factory()->create([
        'email' => 'leaving@example.com', 'keycloak_id' => 'kc-leaving', 'locale' => 'en', 'activated_at' => now()->subYear(),
    ]);
});

it('shows the review tabs', function () {
    actingAsUserAdmin(['ViewAny:User']);

    Livewire::test(ListUsers::class)
        ->assertSuccessful()
        ->assertSee('Possibly moved out')
        ->set('activeTab', 'possibly_moved_out')
        ->assertCanSeeTableRecords([$this->resident]);
});

it('marks a resident as moved out and emails them', function () {
    actingAsUserAdmin(['ViewAny:User', 'Update:User']);

    Livewire::test(ListUsers::class)
        ->callTableAction('markMovedOut', $this->resident);

    expect($this->resident->fresh()->moved_out_at)->not->toBeNull()
        ->and(SentEmail::where('to', 'leaving@example.com')->pluck('mailable')->all())->toBe([ResidentMovedOutMail::class]);
});

it('marks several residents as moved out in bulk', function () {
    actingAsUserAdmin(['ViewAny:User', 'Update:User']);
    $other = User::factory()->create(['email' => 'also-leaving@example.com', 'keycloak_id' => 'kc-also-leaving', 'locale' => 'nl', 'activated_at' => now()->subYear()]);

    Livewire::test(ListUsers::class)
        ->callTableBulkAction('markMovedOut', [$this->resident, $other]);

    expect($this->resident->fresh()->moved_out_at)->not->toBeNull()
        ->and($other->fresh()->moved_out_at)->not->toBeNull()
        ->and(SentEmail::where('mailable', ResidentMovedOutMail::class)->count())->toBe(2);
});

it('only lets admins with delete permission anonymize right away', function () {
    actingAsUserAdmin(['ViewAny:User', 'Update:User']);

    Livewire::test(ListUsers::class)->assertTableActionHidden('anonymizeNow', $this->resident);

    actingAsUserAdmin(['ViewAny:User', 'Delete:User']);

    Livewire::test(ListUsers::class)->callTableAction('anonymizeNow', $this->resident);

    expect($this->resident->fresh()->anonymized_at)->not->toBeNull();
});
