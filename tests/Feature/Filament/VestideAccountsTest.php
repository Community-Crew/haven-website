<?php

use App\Filament\Vestide\Pages\ResidentRecords;
use App\Filament\Vestide\Pages\StaffAccounts;
use App\Models\User;
use App\Services\AccountAlreadyExists;
use App\Services\VestideAccountService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

/**
 * A small in-memory Keycloak: groups by id (with children), users by id,
 * memberships, and a log of every request for assertions.
 */
beforeEach(function () {
    $this->kc = (object) [
        'groups' => [],      // id => ['name' => ..., 'parent' => ?id]
        'users' => [],       // id => representation
        'members' => [],     // groupId => [userId, ...]
        'emails' => [],      // userId => actions[]
        'logouts' => [],
    ];
    $kc = $this->kc;

    Http::fake(function (Request $request) use ($kc) {
        $url = parse_url($request->url());
        $path = preg_replace('#^.*/admin/realms/[^/]+#', '', $url['path']);
        parse_str($url['query'] ?? '', $query);
        $method = $request->method();

        if (str_contains($url['path'], '/protocol/openid-connect/token')) {
            return Http::response(['access_token' => 'fake-admin-token']);
        }

        $children = fn (?string $parent) => collect($kc->groups)
            ->filter(fn ($group) => $group['parent'] === $parent)
            ->map(fn ($group, $id) => ['id' => $id, 'name' => $group['name']])
            ->values()->all();

        if (preg_match('#^/groups(?:/([^/]+)/children)?$#', $path, $m)) {
            $parent = $m[1] ?? null;

            if ($method === 'GET') {
                return Http::response($children($parent));
            }

            $id = 'group-'.(count($kc->groups) + 1);
            $kc->groups[$id] = ['name' => $request->data()['name'], 'parent' => $parent];

            return Http::response(null, 201, ['Location' => "https://kc.test/groups/{$id}"]);
        }

        if (preg_match('#^/groups/([^/]+)/members$#', $path, $m)) {
            return Http::response(collect($kc->members[$m[1]] ?? [])->map(fn ($id) => $kc->users[$id])->values()->all());
        }

        if ($path === '/users' && $method === 'GET') {
            return Http::response(collect($kc->users)->where('email', $query['email'] ?? null)->values()->all());
        }

        if ($path === '/users' && $method === 'POST') {
            $id = 'kc-user-'.(count($kc->users) + 1);
            $kc->users[$id] = ['id' => $id, ...$request->data(), 'requiredActions' => []];

            return Http::response(null, 201, ['Location' => "https://kc.test/users/{$id}"]);
        }

        if (preg_match('#^/users/([^/]+)/groups/([^/]+)$#', $path, $m)) {
            $kc->members[$m[2]] = $method === 'PUT'
                ? array_values(array_unique([...($kc->members[$m[2]] ?? []), $m[1]]))
                : array_values(array_diff($kc->members[$m[2]] ?? [], [$m[1]]));

            return Http::response(null, 204);
        }

        if (preg_match('#^/users/([^/]+)/execute-actions-email$#', $path, $m)) {
            $kc->emails[$m[1]] = $request->data();
            $kc->users[$m[1]]['requiredActions'] = $request->data();

            return Http::response(null, 204);
        }

        if (preg_match('#^/users/([^/]+)/logout$#', $path, $m)) {
            $kc->logouts[] = $m[1];

            return Http::response(null, 204);
        }

        return Http::response(null, 404);
    });

    $this->accounts = app(VestideAccountService::class);
});

function vestideManager(): User
{
    $user = User::factory()->create(['email' => 'manager-'.uniqid().'@vestide.test', 'keycloak_id' => 'kc-manager-'.uniqid()]);
    $user->assignRole(app(VestideAccountService::class)->role(VestideAccountService::MANAGER_GROUP));

    filament()->setCurrentPanel(filament()->getPanel('vestide'));
    test()->actingAs($user);

    return $user;
}

function groupId(object $kc, string $name, ?string $parentName = null): ?string
{
    return collect($kc->groups)->search(fn ($group) => $group['name'] === $name
        && ($parentName === null ? $group['parent'] === null : ($kc->groups[$group['parent']]['name'] ?? null) === $parentName)) ?: null;
}

it('links the manager role to the nested Vestide/Manager group, not a flat twin', function () {
    $role = $this->accounts->role(VestideAccountService::MANAGER_GROUP);

    expect($role->name)->toBe(User::VESTIDE_MANAGER_ROLE)
        ->and($role->keycloak_group_id)->toBe(groupId($this->kc, 'Manager', 'Vestide'))
        ->and(groupId($this->kc, 'Vestide-Manager'))->toBeNull();
});

it('creates an account in the right group and emails the invitation', function () {
    $id = $this->accounts->create('nieuw@vestide.test', 'Nina', 'Nieuw', manager: false);

    expect($this->kc->users[$id]['email'])->toBe('nieuw@vestide.test')
        ->and($this->kc->members[groupId($this->kc, 'Vestide')])->toContain($id)
        ->and($this->kc->emails[$id])->toBe(['UPDATE_PASSWORD', 'VERIFY_EMAIL']);

    $account = $this->accounts->accounts()->firstWhere('id', $id);
    expect($account['name'])->toBe('Nina Nieuw')
        ->and($account['manager'])->toBeFalse()
        ->and($account['invited'])->toBeTrue();
});

it('refuses an email that already has an account, rather than granting it access', function () {
    $this->kc->users['resident'] = ['id' => 'resident', 'email' => 'bewoner@example.com', 'requiredActions' => []];

    expect(fn () => $this->accounts->create('bewoner@example.com', 'Bea', 'Bewoner', manager: false))
        ->toThrow(AccountAlreadyExists::class);

    expect($this->kc->members)->each->not->toContain('resident');
});

it('revokes access in Keycloak and in Haven straight away', function () {
    $id = $this->accounts->create('weg@vestide.test', 'Wim', 'Weg', manager: true);
    $local = User::factory()->create(['email' => 'weg@vestide.test', 'keycloak_id' => $id]);
    $local->assignRole($this->accounts->role(VestideAccountService::MANAGER_GROUP));

    $this->accounts->revoke($id);

    expect($this->kc->members[groupId($this->kc, 'Manager', 'Vestide')])->not->toContain($id)
        ->and($this->kc->logouts)->toContain($id)
        ->and($local->fresh()->canAccessPanel(filament()->getPanel('vestide')))->toBeFalse();
});

it('only lets Vestide managers (and super admins) manage accounts', function () {
    $staff = User::factory()->create(['email' => 'staff@vestide.test', 'keycloak_id' => 'kc-staff']);
    $staff->assignRole($this->accounts->role(VestideAccountService::STAFF_GROUP));
    $base = 'http://'.config('services.admin_domain').'/vestide';

    $this->actingAs($staff)->get($base.'/staff-accounts')->assertForbidden();
    $this->actingAs($staff)->get($base)->assertOk()->assertDontSee('Medewerkers');

    $manager = vestideManager();
    $this->actingAs($manager)->get($base.'/staff-accounts')->assertOk()->assertSee('Medewerkers van Vestide');
});

it('lets a manager add a colleague from the page, but not revoke themselves', function () {
    $manager = vestideManager();
    $this->kc->members[$this->accounts->role(VestideAccountService::MANAGER_GROUP)->keycloak_group_id] = [$manager->keycloak_id];
    $this->kc->users[$manager->keycloak_id] = ['id' => $manager->keycloak_id, 'firstName' => 'Mia', 'lastName' => 'Manager', 'email' => $manager->email, 'requiredActions' => []];

    Livewire::test(StaffAccounts::class)
        ->callAction('create', data: ['first_name' => 'Nina', 'last_name' => 'Nieuw', 'email' => 'Nina@Vestide.test', 'manager' => false])
        ->assertHasNoActionErrors()
        ->assertNotified('Medewerker toegevoegd')
        ->assertSee('Nina Nieuw')
        ->assertSee('Uitgenodigd')
        ->assertTableActionHidden('revoke', $manager->keycloak_id)
        ->assertTableActionVisible('revoke', collect($this->kc->users)->firstWhere('email', 'nina@vestide.test')['id']);
});

it('keeps Vestide staff out of the resident lookup', function () {
    $manager = vestideManager();
    $manager->update(['name' => 'Mia Manager']);

    Livewire::test(ResidentRecords::class)
        ->searchTable('Mia')
        ->assertCanNotSeeTableRecords([$manager]);
});

it('offers super admins a switch between the two panels', function () {
    $admin = User::factory()->create(['email' => 'super@example.com', 'keycloak_id' => 'kc-super']);
    $admin->assignRole(Role::firstOrCreate(['name' => config('filament-shield.super_admin.name'), 'guard_name' => 'web'], ['keycloak_group_id' => 'kc-admin-group']));
    $domain = 'http://'.config('services.admin_domain');

    $this->actingAs($admin)->get($domain.'/vestide')->assertOk()->assertSee('Naar het Haven-beheer');
    $this->actingAs($admin)->get($domain.'/')->assertOk()->assertSee('Vestide dashboard');

    $this->actingAs(vestideManager())->get($domain.'/vestide')->assertDontSee('Naar het Haven-beheer');
});
