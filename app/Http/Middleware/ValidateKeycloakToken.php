<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Exception;
use Firebase\JWT\JWK;
// Make sure this is imported to decode
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
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
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
                    throw new \Exception('Could not connect to Keycloak to retrieve JWKS.');
                }

                $data = $response->json();

                if (! is_array($data) || ! isset($data['keys'])) {
                    throw new \Exception('Keycloak returned an invalid JWK Set payload.');
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

            $tokenIssuedAt = isset($decoded->iat) ? now()->setTimestamp($decoded->iat) : null;
            if ($user->wasRecentlyCreated || ! $user->updated_at || ($tokenIssuedAt && $tokenIssuedAt->gt($user->updated_at))) {
                foreach ($mappedRoles as $roleName) {
                    Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
                }

                $user->syncRoles($mappedRoles);
                $user->touch();
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
