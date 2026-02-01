<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    public function logout(): RedirectResponse
    {
        Auth::logout();

        return redirect(config('services.keycloak.base_url').'/realms/'.config('services.keycloak.realm').'/protocol/openid-connect/logout');
    }
}
