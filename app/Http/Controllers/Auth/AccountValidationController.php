<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\RegistrationCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class AccountValidationController extends Controller
{
    public function store(Request $request)
    {
        $keycloak_id = $request->session()->get('keycloak_id');

        if (! $keycloak_id) {
            return Redirect::route('/');
        }

        $code = $request->get('registration_code');
        $registrationCode = RegistrationCode::Where(['code' => $code])->first();

        if ($registrationCode == null) {
            return Inertia::render('auth/Validation', ['error' => 'Invalid registration code']);
        }

        if ($registrationCode['is_used']) {
            return Inertia::render('auth/Validation', ['error' => 'Registration code has been used already!']);
        }

        $user = User::where('keycloak_id', $keycloak_id)->firstOrFail();

        $this->updateUserInKeycloak($keycloak_id);

        $user['unit_id'] = $registrationCode['unit_id'];
        $user->save();

        $registrationCode['is_used'] = true;
        $registrationCode->save();

        Auth::login($user, true);
        $request->session()->regenerate();

        return Redirect::route('home');
    }

    private function updateUserInKeycloak(string $keycloak_id)
    {
        try {
            $tokenResponse = Http::asForm()->post(config('keycloak.authServerUrl').'/realms/'.config('keycloak.realm').'/protocol/openid-connect/token', [
                'client_id' => config('keycloak.client_id'),
                'client_secret' => config('keycloak.client_secret'),
                'grant_type' => 'client_credentials',
            ]);

            if ($tokenResponse->failed()) {
                return false;
            }

            $accessToken = $tokenResponse->json()['access_token'];
            $userUrl = config('keycloak.authServerUrl').'/admin/realms/'.config('keycloak.realm').'/users/'.$keycloak_id;

            $getResponse = Http::withToken($accessToken)->get($userUrl);
            if ($getResponse->failed()) {
                Log::error('Failed to GET Keycloak user '.$keycloak_id.': '.$getResponse->body());

                return false;
            }
            $userRepresentation = $getResponse->json();

            $userRepresentation['attributes']['validated'] = ['yes'];

            $updateResponse = Http::withToken($accessToken)->put($userUrl, $userRepresentation);

            if ($updateResponse->failed()) {
                dd(
                    'URL Sent:', $userUrl,
                    'HTTP Status:', $updateResponse->status(),
                    'Response Body:', $updateResponse->json() // or ->body() if it's not JSON
                );
            }

            return $updateResponse->successful();
        } catch (\Exception $exception) {
            Log::error('Keycloak user update failed: '.$exception->getMessage());

            return false;
        }

    }
}
