<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Keycloak\KeycloakAdminService;
use Exception;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;
use Throwable;

class KeycloakAdminController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('keycloak')->redirect();
    }

    public function callback()
    {
        try {
            $keycloakUser = Socialite::driver('keycloak')->user();
        } catch (Exception $e) {
            activity('auth')
                ->event('login_failed')
                ->withProperties(['ip' => request()->ip()])
                ->log('Keycloak authentication failed');

            return redirect()->route('filament.admin.auth.login')->with('error', 'Keycloak authentication failed.');
        }

        $user = User::firstOrCreate(
            ['keycloak_id' => $keycloakUser->getId()],
            [
                'name' => $keycloakUser->getName(),
                'email' => $keycloakUser->getEmail(),
            ]
        );

        $keycloakRoles = $keycloakUser->getRaw()['groups'] ?? [];

        if (! (empty($keycloakRoles))) {
            $cleanedRoles = [];
            foreach ($keycloakRoles as $groupPath) {
                $roleName = trim(str_replace('/', '-', $groupPath), '-');

                if (! Role::query()->where('name', $roleName)->where('guard_name', 'web')->exists()) {
                    $this->createRoleForGroup($roleName, $groupPath);
                }

                $cleanedRoles[] = $roleName;
            }
            $user->syncRoles($cleanedRoles);
        } else {
            $user->syncRoles([]);
        }

        Filament::auth()->login($user);

        if ($user->isVestideOnly()) {
            return redirect()->to(Filament::getPanel('vestide')->getUrl());
        }

        return redirect()->intended('/');
    }

    /**
     * A role for a group the user is already in. Created quietly and linked
     * to that group: RoleObserver would provision a group named after the
     * flat role name instead, so a nested group like /Vestide/Manager would
     * get a stray top-level "Vestide-Manager" twin. Best-effort - without the
     * link the role still works, it just isn't synced back to Keycloak when
     * assigned from the admin.
     */
    private function createRoleForGroup(string $roleName, string $groupPath): void
    {
        $role = Role::withoutEvents(fn () => Role::create(['name' => $roleName, 'guard_name' => 'web']));

        try {
            $role->keycloak_group_id = app(KeycloakAdminService::class)->findOrCreateGroupPath($groupPath)['id'];
            $role->saveQuietly();
        } catch (Throwable $e) {
            Log::warning('Could not link new role to its Keycloak group', ['role' => $roleName, 'error' => $e->getMessage()]);
        }
    }
}
