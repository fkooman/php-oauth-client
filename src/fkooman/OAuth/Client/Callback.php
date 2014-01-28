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

class Callback
{
    private $clientConfigId;
    private $clientConfig;
    private $tokenStorage;
    private $httpClient;

    public function __construct($clientConfigId, ClientConfigInterface $clientConfig, StorageInterface $tokenStorage, \Guzzle\Http\Client $httpClient)
    {
        $this->setClientConfigId($clientConfigId);
        $this->setClientConfig($clientConfig);
        $this->setTokenStorage($tokenStorage);
        $this->setHttpClient($httpClient);
    }

    public function setClientConfigId($clientConfigId)
    {
        if (!is_string($clientConfigId) || 0 >= strlen($clientConfigId)) {
            throw new ApiException("clientConfigId should be a non-empty string");
        }
        $this->clientConfigId = $clientConfigId;
    }

    public function setClientConfig(ClientConfigInterface $clientConfig)
    {
        $this->clientConfig = $clientConfig;
    }

    public function setTokenStorage(StorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function setHttpClient(\Guzzle\Http\Client $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function handleCallback(array $query)
    {
        $qState = isset($query['state']) ? $query['state'] : null;
        $qCode = isset($query['code']) ? $query['code'] : null;
        $qError = isset($query['error']) ? $query['error'] : null;
        $qErrorDescription = isset($query['error_description']) ? $query['error_description'] : null;

        if (null === $qState) {
            throw new CallbackException("state parameter missing");
        }
        $state = $this->tokenStorage->getState($this->clientConfigId, $qState);
        if (false === $state) {
            throw new CallbackException("state not found");
        }

        // avoid race condition for state by really needing a confirmation
        // that it was deleted
        if (false === $this->tokenStorage->deleteState($state)) {
            throw new CallbackException("state already used");
        }

        if (null === $qCode && null === $qError) {
            throw new CallbackException("both code and error parameter missing");
        }

        if (null !== $qError) {
            // FIXME: this should probably be CallbackException?
            throw new AuthorizeException($qError, $qErrorDescription);
        }

        if (null !== $qCode) {
            $t = new TokenRequest($this->httpClient, $this->clientConfig);
            $tokenResponse = $t->withAuthorizationCode($qCode, $state);
            if (false === $tokenResponse) {
                throw new CallbackException("unable to fetch access token with authorization code");
            }

            if (null === $tokenResponse->getScope()) {
                // no scope in response, we assume we got the requested scope
                $scope = $state->getScope();
            } else {
                // the scope we got should be a superset of what we requested
                $scope = $tokenResponse->getScope();
                if (!$scope->hasScope($state->getScope())) {
                    // we didn't get the scope we requested, stop for now
                    // FIXME: we need to implement a way to request certain
                    // scope as being optional, while others need to be
                    // required
                    throw new CallbackException("requested scope not obtained");
                }
            }

            // store the access token
            $accessToken = new AccessToken(
                array(
                    "client_config_id" => $this->clientConfigId,
                    "user_id" => $state->getUserId(),
                    "scope" => $scope,
                    "access_token" => $tokenResponse->getAccessToken(),
                    "token_type" => $tokenResponse->getTokenType(),
                    "issue_time" => time(),
                    "expires_in" => $tokenResponse->getExpiresIn()
                )
            );
            $this->tokenStorage->storeAccessToken($accessToken);

            // if we also got a refresh token in the response, store that as
            // well
            if (null !== $tokenResponse->getRefreshToken()) {
                $refreshToken = new RefreshToken(
                    array(
                        "client_config_id" => $this->clientConfigId,
                        "user_id" => $state->getUserId(),
                        "scope" => $scope,
                        "refresh_token" => $tokenResponse->getRefreshToken(),
                        "issue_time" => time()
                    )
                );
                $this->tokenStorage->storeRefreshToken($refreshToken);
            }

            return $accessToken;
        }
    }
}
