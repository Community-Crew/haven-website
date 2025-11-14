<?php

namespace App\Providers\auth;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;

class KeycloakProvider extends AbstractProvider implements ProviderInterface
{

    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['openid', 'profile', 'email'];

    /**
     * The separating character for the requested scopes.
     *
     * @var string
     */
    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state): string
    {
        $this->request->session()->put('code_verifier', $verifier = Str::random(128));

        $challenge = strtr(rtrim(base64_encode(hash('sha256', $verifier, true)),
            '='
        ), '+/', '-_');

        $baseUrl = rtrim(config('services.keycloak.base_url'), '/');
        $realm = config('services.keycloak.realm');
        $authUrl = "{$baseUrl}/realms/{$realm}/protocol/openid-connect/auth";

        return $this->buildAuthUrlFromBase($authUrl, $state)
            .'&code_challenge='.$challenge
            .'&code_challenge_method=S256';
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl(): int|string
    {
        $baseUrl = rtrim(config('services.keycloak.base_url'), '/');
        $realm = config('services.keycloak.realm');
        return "{$baseUrl}/realms/{$realm}/protocol/openid-connect/token";
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code): array
    {
        $fields = parent::getTokenFields($code);

        $fields['code_verifier'] = $this->request->session()->pull('code_verifier');

        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $baseUrl = rtrim(config('services.keycloak.base_url'), '/');
        $realm = config('services.keycloak.realm');
        $userinfoUrl = "{$baseUrl}/realms/{$realm}/protocol/openid-connect/userinfo";

        $response = Http::withToken($token)->get($userinfoUrl);

        return $response->json();
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id' => $user['sub'],
            'is_validated' => $user['validated'],
            'name' => $user['name'],
            'email' => $user['email'],
        ]);
    }
}
