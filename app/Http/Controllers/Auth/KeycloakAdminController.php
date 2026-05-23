<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

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
            return redirect()->route('filament.admin.auth.login')->with('error', 'Keycloak authentication failed.');
        }

        $user = User::firstOrCreate(
            ['keycloak_id' => $keycloakUser->getId()],
            [
                'name' => $keycloakUser->getName(),
                'email' => $keycloakUser->getEmail(),
            ]
        );

        Filament::auth()->login($user);

        return redirect()->intended('/');
    }
}
