<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Socialite;

class LogoutController extends Controller
{
    public function logout(){
        Auth::logout();
        return redirect(config('services.keycloak.base_url').'/realms/'.config('services.keycloak.realm').'/protocol/openid-connect/logout');
    }
}
