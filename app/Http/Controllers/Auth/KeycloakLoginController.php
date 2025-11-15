<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Laravel\Socialite\Socialite;


class KeycloakLoginController extends Controller
{
    public function callback(Request $request){
        $keycloak_user = Socialite::driver('keycloak')->user();
        $user = User::updateOrCreate(
            ['keycloak_id' => $keycloak_user->getId()],
            [
                'name' => $keycloak_user->getName(),
                'email' => $keycloak_user->getEmail()
            ]
        );

        $user_is_validated = $keycloak_user->user['validated'];

        if($user_is_validated == 'yes'){
            Auth::login($user);
            return redirect('/');
        } else {
            $request->session()->put('keycloak_id', $user->keycloak_id);

            return Inertia::render('auth/Validation', []);
        }
    }

    public function redirect(Request $request){
        return Socialite::driver('keycloak')->redirect();
    }

    public function register(Request $request){
        return redirect(config('services.keycloak.base_url').config('services.keycloak.realm').'account');
    }

}
