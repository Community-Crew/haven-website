<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class HandleKeycloakAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        $accessToken = session('keycloak_token') ?? $user->keycloak_token;

        if (!$accessToken) {
            return $this->forceLogout($request);
        }

        $parts = explode('.', $accessToken);
        if (count($parts) !== 3) return $this->forceLogout($request);

        $payload = json_decode(base64_decode($parts[1]), true);
        $exp = $payload['exp'] ?? 0;

        if (time() >= $exp) {
            $refreshResult = $this->refreshKeycloakToken($request, $user);

            if ($refreshResult === null) {
                return $next($request);
            }

            return $refreshResult;
        }

        if (!session()->has('roles')) {
            $client = config('services.keycloak.client_id');
            $roles = $payload['resource_access'][$client]['roles'] ?? [];
            session(['roles' => $roles, 'keycloak_token' => $accessToken]);
        }

        return $next($request);
    }

    protected function refreshKeycloakToken($request, $user)
    {
        if (!$user->keycloak_refresh_token) {
            return $this->forceLogout($request);
        }

        $response = Http::asForm()->post(config('services.keycloak.base_url') . "/realms/" . config('services.keycloak.realm') . "/protocol/openid-connect/token", [
            'grant_type' => 'refresh_token',
            'refresh_token' => $user->keycloak_refresh_token,
            'client_id' => config('services.keycloak.client_id'),
            'client_secret' => config('services.keycloak.client_secret'),
        ]);

        if ($response->successful()) {
            $data = $response->json();

            $user->update([
                'keycloak_token' => $data['access_token'],
                'keycloak_refresh_token' => $data['refresh_token'] ?? $user->keycloak_refresh_token,
            ]);

            session([
                'keycloak_token' => $data['access_token'],
                'roles' => $this->parseRoles($data['access_token'])
            ]);

            Auth::setUser($user);

            return null;
        }

        return $this->forceLogout($request);
    }

    protected function forceLogout($request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->to('/auth/login/redirect');
    }

    /**
     * Helper to extract roles from a raw JWT string.
     */
    private function parseRoles(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return [];

        $payload = json_decode(base64_decode($parts[1]), true);
        $clientId = config('services.keycloak.client_id');

        return $payload['resource_access'][$clientId]['roles'] ?? [];
    }
}
