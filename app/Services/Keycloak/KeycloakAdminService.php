<?php

namespace App\Services\Keycloak;

use App\Traits\LogsFailedHttpResponses;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class KeycloakAdminService
{
    use LogsFailedHttpResponses;

    /**
     * Base URL for Keycloak's Admin REST API (group/user management) - as
     * opposed to the OIDC token endpoint below, which lives under /realms
     * rather than /admin/realms.
     */
    private function adminApiBase(): string
    {
        return config('services.keycloak.base_url').'/admin/realms/'.config('services.keycloak.realms');
    }

    /**
     * The client_credentials token endpoint used to authenticate as the
     * admin service account (KEYCLOAK_ADMIN_CLIENT_ID/SECRET) - distinct
     * from the OIDC login client used for interactive admin sign-in.
     */
    private function tokenEndpoint(): string
    {
        return config('services.keycloak.base_url').'/realms/'.config('services.keycloak.realms').'/protocol/openid-connect/token';
    }

    private function token(): string
    {
        return Cache::remember('keycloak_admin_token', 50, function () {
            $response = Http::asForm()->post($this->tokenEndpoint(), [
                'grant_type' => 'client_credentials',
                'client_id' => config('services.keycloak.admin_client_id'),
                'client_secret' => config('services.keycloak.admin_client_secret'),
            ]);

            $this->throwIfFailed($response, 'Keycloak admin token fetch failed');

            $token = $response->json('access_token');

            // A 2xx response isn't enough on its own - throwIfFailed() only
            // checks the HTTP status, so a 200 with an unexpected body
            // (misconfigured admin client, Keycloak API shape change, ...)
            // would otherwise reach the `: string` return type below as
            // null and TypeError there instead, which is a confusing shape
            // for the best-effort callers (RoleObserver et al.) that catch
            // Throwable and log it - they'd log a raw TypeError message
            // indistinguishable from an unrelated bug, instead of this
            // being clearly a Keycloak-response problem.
            if (! is_string($token) || $token === '') {
                Log::warning('Keycloak admin token response missing access_token', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new RuntimeException('Keycloak admin token response missing access_token');
            }

            return $token;
        });
    }

    /**
     * Finds (or creates, segment by segment) the Keycloak group at the
     * given slash-separated path - e.g. "commission/garden/chair" or
     * "board/chair" - matching the nested layout the existing OIDC login
     * callback already parses (KeycloakAdminController::callback()).
     *
     * Keycloak has no "create by path" endpoint, so each segment is
     * resolved (or created) under its parent in turn via the group-children
     * endpoints.
     *
     * @return array{id: string, name: string} the leaf group
     */
    public function findOrCreateGroupPath(string $path): array
    {
        $segments = explode('/', trim($path, '/'));
        $parentId = null;
        $group = null;

        foreach ($segments as $segment) {
            $group = $this->findChildByName($parentId, $segment)
                ?? $this->createChildGroup($parentId, $segment);

            $parentId = $group['id'];
        }

        return $group;
    }

    /**
     * Deletes a group by id - Keycloak cascades this to any subgroups, but
     * leaves a now-empty parent (e.g. deleting "chair" under
     * "commission/garden" leaves "commission/garden" itself in place).
     * That's left alone deliberately rather than pruned, to keep this a
     * single unconditional call.
     */
    public function deleteGroup(string $keycloakGroupId): void
    {
        $response = Http::withToken($this->token())
            ->delete($this->adminApiBase()."/groups/{$keycloakGroupId}");

        $this->throwIfFailed($response, 'Keycloak group deletion failed');
    }

    /**
     * @return array{id: string, name: string}|null
     */
    private function findChildByName(?string $parentId, string $name): ?array
    {
        $endpoint = $parentId
            ? $this->adminApiBase()."/groups/{$parentId}/children"
            : $this->adminApiBase().'/groups';

        $response = Http::withToken($this->token())->get($endpoint, ['search' => $name]);

        $this->throwIfFailed($response, 'Keycloak group lookup failed');

        // 'search' matches substrings, so narrow down to the exact name.
        return collect($response->json())->firstWhere('name', $name);
    }

    /**
     * @return array{id: string, name: string}
     */
    private function createChildGroup(?string $parentId, string $name): array
    {
        $endpoint = $parentId
            ? $this->adminApiBase()."/groups/{$parentId}/children"
            : $this->adminApiBase().'/groups';

        $response = Http::withToken($this->token())->post($endpoint, ['name' => $name]);

        $this->throwIfFailed($response, 'Keycloak group creation failed');

        // Group creation returns 201 with an empty body - the new group's
        // id is only available via the Location header.
        return ['id' => basename($response->header('Location')), 'name' => $name];
    }

    /**
     * Adds a user to a group - idempotent, Keycloak returns 204 whether or
     * not they were already a member.
     */
    public function addUserToGroup(string $keycloakUserId, string $keycloakGroupId): void
    {
        $response = Http::withToken($this->token())
            ->put($this->adminApiBase()."/users/{$keycloakUserId}/groups/{$keycloakGroupId}");

        $this->throwIfFailed($response, 'Keycloak add user to group failed');
    }

    /**
     * Removes a user from a group - idempotent, Keycloak returns 204 whether
     * or not they were a member.
     */
    public function removeUserFromGroup(string $keycloakUserId, string $keycloakGroupId): void
    {
        $response = Http::withToken($this->token())
            ->delete($this->adminApiBase()."/users/{$keycloakUserId}/groups/{$keycloakGroupId}");

        $this->throwIfFailed($response, 'Keycloak remove user from group failed');
    }
}
