<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Exception;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

class ValidateKeycloakToken
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['message' => 'Authorization Bearer token required.'], 401);
        }

        try {
            $jwksUrl = rtrim(
                config('services.keycloak.base_url'),
                '/'
            ).'/realms/'.config('services.keycloak.realms').'/protocol/openid-connect/certs';

            $jwksData = Cache::remember('keycloak_jwks', 86400, function () use ($jwksUrl) {
                $response = Http::withHeaders(['Accept' => 'application/json'])->get($jwksUrl);

                if ($response->failed()) {
                    throw new Exception('Could not connect to Keycloak to retrieve JWKS.');
                }

                $data = $response->json();

                if (! is_array($data) || ! isset($data['keys'])) {
                    throw new Exception('Keycloak returned an invalid JWK Set payload.');
                }

                return $data;
            });

            $keys = JWK::parseKeySet($jwksData);

            $decoded = JWT::decode($token, $keys);

            $user = User::where('keycloak_id', $decoded->sub)->first();

            $keycloakGroups = $decoded->groups ?? [];
            $mappedRoles = [];

            foreach ($keycloakGroups as $group) {
                $cleanRoleName = trim(str_replace('/', '-', $group), '-');
                if (! empty($cleanRoleName)) {
                    $mappedRoles[] = $cleanRoleName;
                }
            }

            if (! $user) {
                $user = User::create([
                    'keycloak_id' => $decoded->sub,
                    'name' => $decoded->name ?? 'Keycloak User',
                    'email' => $decoded->email ?? null,
                ]);
            }

            // Keycloak issues a fresh `iat` on every refreshed access token
            // (the frontend refreshes roughly every minute), so comparing
            // timestamps here made this expensive resync run on almost every
            // request for every logged-in user. Comparing the actual role
            // sets instead means the cheap path (nothing changed) is just
            // one query, and the sync only runs when Keycloak's groups for
            // this user genuinely changed.
            $currentRoleNames = $user->getRoleNames()->sort()->values()->all();
            $mappedRoleNamesSorted = collect($mappedRoles)->sort()->values()->all();

            if ($user->wasRecentlyCreated || $currentRoleNames !== $mappedRoleNamesSorted) {
                foreach ($mappedRoles as $roleName) {
                    Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
                }

                $user->syncRoles($mappedRoles);
            }

            Auth::setUser($user);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Invalid or expired token.',
                'error' => $e->getMessage(),
            ], 401);
        }

        return $next($request);
    }
}
