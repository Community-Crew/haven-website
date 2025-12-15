<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\InvalidStateException;


class KeycloakLoginController extends Controller
{
    public function callback(Request $request)
    {
        try {
            $keycloak_user = Socialite::driver('keycloak')->user();
        } catch (InvalidStateException) {
            return redirect('auth/login/redirect');
        }
        $user = User::updateOrCreate(
            ['keycloak_id' => $keycloak_user->getId()],
            [
                'name' => $keycloak_user->getName(),
                'email' => $keycloak_user->getEmail()
            ]
        );
        $user_is_validated = $keycloak_user->user['validated'] ?? "no";

        // Get groups
        $access_token = $keycloak_user->token;
        list($header, $payload, $signature) = explode(".", $access_token);
        $claims = json_decode(base64_decode($payload), true);

        $clientName = config('services.keycloak.client_id');
        $roles = $claims['resource_access'][$clientName]['roles'] ?? [];
        $groupsFromToken = $claims['groups'] ?? [];

        Session::put('roles', $roles);

        $this->syncUserGroups($user, $groupsFromToken);



        if ($user_is_validated == 'yes') {
            Auth::login($user);
            return redirect('/');
        } else {
            $request->session()->put('keycloak_id', $user->keycloak_id);

            return Inertia::render('auth/Validation', []);
        }
    }

    public function redirect(Request $request)
    {
        return Socialite::driver('keycloak')->redirect();
    }

    public function register(Request $request)
    {
        return redirect(config('services.keycloak.base_url') . config('services.keycloak.realm') . 'account');
    }

    protected function syncUserGroups(User $user, array $groupsFromToken)
    {
        $groupIds = [];
        foreach ($groupsFromToken as $groupName) {
            $group = Group::firstOrCreate(
                ['name' => $groupName],
            );
            $groupIds[] = $group->id;
        }

        $user->groups()->sync($groupIds);
    }

}
