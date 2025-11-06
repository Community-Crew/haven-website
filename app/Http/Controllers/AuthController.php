<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Socialite;


class AuthController extends Controller
{
    public function callback(Request $request){
        $user = Socialite::driver('keycloak')->user();
        dd($user);
    }

    public function redirect(Request $request){
        return Socialite::driver('keycloak')->redirect();
    }
}
