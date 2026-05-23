<?php

namespace App\Providers\auth;

use GuzzleHttp\RequestOptions;
use Illuminate\Support\Arr;
use SocialiteProviders\Manager\Exception\InvalidArgumentException;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;
use SocialiteProviders\Manager\OAuth2\User;

class KeycloakProvider extends AbstractProvider
{
    public const IDENTIFIER = 'KEYCLOAK';

    protected $usesPKCE = true;

    protected $scopeSeparator = ' ';

    protected $scopes = ['openid'];

    public static function additionalConfigKeys(): array
    {
        return ['base_url', 'realms'];
    }

    protected function getBaseUrl()
    {
        return rtrim(rtrim($this->getConfig('base_url'), '/') . '/realms/' . $this->getConfig('realms', 'master'), '/');
    }

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase($this->getBaseUrl() . '/protocol/openid-connect/auth', $state);
    }

    protected function getTokenUrl(): string
    {
        return $this->getBaseUrl() . '/protocol/openid-connect/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get($this->getBaseUrl() . '/protocol/openid-connect/userinfo', [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);

        return json_decode((string)$response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id' => Arr::get($user, 'sub'),
            'nickname' => Arr::get($user, 'preferred_username'),
            'name' => Arr::get($user, 'name'),
            'email' => Arr::get($user, 'email'),
        ]);
    }

    /**
     * Return logout endpoint with redirect_uri, clientId, idTokenHint
     * and optional parameters by a key value array.
     *
     * @param string|null $redirectUri
     * @param string|null $clientId
     * @param string|null $idTokenHint
     * @param array $additionalParameters
     * @return string
     *
     * @throws InvalidArgumentException
     */
    public function getLogoutUrl(?string $redirectUri = null, ?string $clientId = null, ?string $idTokenHint = null, ...$additionalParameters): string
    {
        $logoutUrl = $this->getBaseUrl() . '/protocol/openid-connect/logout';

        $logoutUrl .= '?post_logout_redirect_uri=' . urlencode($redirectUri);

        if ($clientId !== null) {
            $logoutUrl .= '&client_id=' . urlencode($clientId);
        }

        if ($idTokenHint !== null) {
            $logoutUrl .= '&id_token_hint=' . urlencode($idTokenHint);
        }

        foreach ($additionalParameters as $parameter) {
            if (!is_array($parameter) || count($parameter) > 1) {
                throw new InvalidArgumentException('Invalid argument. Expected an array with a key and a value.');
            }

            $parameterKey = array_keys($parameter)[0];
            $parameterValue = array_values($parameter)[0];

            $logoutUrl .= "&{$parameterKey}=" . urlencode($parameterValue);
        }

        return $logoutUrl;
    }

    protected function getCodeChallengeMethod()
    {
        return 'S256';
    }
}
