<?php

/**
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Lesser General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Lesser General Public License for more details.
 *
 *  You should have received a copy of the GNU Lesser General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace fkooman\OAuth\Client;

use cdyweb\http\Adapter;
use RuntimeException;

class TokenRequest
{
    /**
     * @var Adapter
     */
    private $httpClient;

    /**
     * @var \fkooman\OAuth\Client\ClientConfigInterface
     */
    private $clientConfig;

    public function __construct(Adapter $httpClient, ClientConfigInterface $clientConfig)
    {
        $this->httpClient = $httpClient;
        $this->clientConfig = $clientConfig;
    }

    public function withAuthorizationCode($authorizationCode)
    {
        $postFields = array(
            'code' => $authorizationCode,
            'grant_type' => 'authorization_code',
        );
        if (null !== $this->clientConfig->getRedirectUri()) {
            $postFields['redirect_uri'] = $this->clientConfig->getRedirectUri();
        }

        return $this->accessTokenRequest($postFields);
    }

    public function withRefreshToken($refreshToken)
    {
        $postFields = array(
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        );
        // Some services require specifying the redirect_uri also when using
        // the refresh_token.
        // issue: https://github.com/fkooman/php-oauth-client/issues/20
        if ($this->clientConfig->getUseRedirectUriOnRefreshTokenRequest()) {
            if (null !== $this->clientConfig->getRedirectUri()) {
                $postFields['redirect_uri'] = $this->clientConfig->getRedirectUri();
            }
        }

        return $this->accessTokenRequest($postFields);
    }

    private function accessTokenRequest(array $postFields)
    {
        if ($this->clientConfig->getCredentialsInRequestBody()) {
            // provide credentials in the POST body
            $postFields['client_id'] = $this->clientConfig->getClientId();
            $postFields['client_secret'] = $this->clientConfig->getClientSecret();
        } else {
            // use basic authentication
            $this->httpClient->setBasicAuth($this->clientConfig->getClientId(), $this->clientConfig->getClientSecret());
        }

        try {
            $psr_response = $this->httpClient->post(
                $this->clientConfig->getTokenEndpoint(),
                array('Accept'=>'application/json'),
                $postFields
            );
            $responseData = json_decode($psr_response->getBody(), true);

            // some servers do not provide token_type, so we allow for setting
            // a default
            // issue: https://github.com/fkooman/php-oauth-client/issues/13
            if (null !== $this->clientConfig->getDefaultTokenType()) {
                if (is_array($responseData) && !isset($responseData['token_type'])) {
                    $responseData['token_type'] = $this->clientConfig->getDefaultTokenType();
                }
            }

            // if the field "expires_in" has the value null, remove it
            // issue: https://github.com/fkooman/php-oauth-client/issues/17
            if ($this->clientConfig->getAllowNullExpiresIn()) {
                if (is_array($responseData) && array_key_exists('expires_in', $responseData)) {
                    if (null === $responseData['expires_in']) {
                        unset($responseData['expires_in']);
                    }
                }
            }

            // if the field "scope" is empty string a default can be set
            // through the client configuration
            // issue: https://github.com/fkooman/php-oauth-client/issues/20
            if (null !== $this->clientConfig->getDefaultServerScope()) {
                if (is_array($responseData) && isset($responseData['scope']) && '' === $responseData['scope']) {
                    $responseData['scope'] = $this->clientConfig->getDefaultServerScope();
                }
            }

            // the service can return a string value of the expires_in
            // parameter, allow to convert to number instead
            // issue: https://github.com/fkooman/php-oauth-client/issues/40
            if ($this->clientConfig->getAllowStringExpiresIn()) {
                if (is_array($responseData) && isset($responseData['expires_in']) && is_string($responseData['expires_in'])) {
                    $responseData['expires_in'] = intval($responseData['expires_in']);
                }
            }

            if ($this->clientConfig->getUseCommaSeparatedScope()) {
                if (is_array($responseData) && isset($responseData['scope'])) {
                    $responseData['scope'] = str_replace(',', ' ', $responseData['scope']);
                }
            }

            // issue: https://github.com/fkooman/php-oauth-client/issues/41
            if ($this->clientConfig->getUseArrayScope()) {
                if (is_array($responseData) && isset($responseData['scope'])) {
                    if (is_array($responseData['scope'])) {
                        $responseData['scope'] = implode(' ', $responseData['scope']);
                    }
                }
            }

            return new TokenResponse($responseData);
        } catch (RuntimeException $e) {
            if (strpos(get_class($e),'PHPUnit')!==false) throw $e;
            return false;
        }
    }
}
