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

class ClientConfig implements ClientConfigInterface
{
    // VSCHAR     = %x20-7E
    const REGEXP_VSCHAR = '/^(?:[\x20-\x7E])*$/';

    private $clientId;
    private $authorizeEndpoint;
    private $tokenEndpoint;
    private $clientSecret;
    private $redirectUri;
    private $credentialsInRequestBody;
    private $defaultTokenType;
    private $allowNullExpiresIn;
    private $enableDebug;
    private $defaultServerScope;
    private $useRedirectUriOnRefreshTokenRequest;
    private $appendStateToRedirectUri;


    public function __construct(array $data)
    {
        foreach (array('client_id', 'authorize_endpoint', 'token_endpoint') as $key) {
            if (!array_key_exists($key, $data)) {
                throw new ClientConfigException(sprintf("missing field '%s'", $key));
            }
        }
        $this->setClientId($data['client_id']);
        $this->setAuthorizeEndpoint($data['authorize_endpoint']);
        $this->setTokenEndpoint($data['token_endpoint']);

        $clientSecret = array_key_exists('client_secret', $data) ? $data['client_secret'] : null;
        $this->setClientSecret($clientSecret);

        $redirectUri = array_key_exists('redirect_uri', $data) ? $data['redirect_uri'] : null;
        $this->setRedirectUri($redirectUri);

        $credentialsInRequestBody = array_key_exists('credentials_in_request_body', $data) ? $data['credentials_in_request_body'] : false;
        $this->setCredentialsInRequestBody($credentialsInRequestBody);

        $defaultTokenType = array_key_exists('default_token_type', $data) ? $data['default_token_type'] : null;
        $this->setDefaultTokenType($defaultTokenType);

        $allowNullExpiresIn = array_key_exists('allow_null_expires_in', $data) ? $data['allow_null_expires_in'] : false;
        $this->setAllowNullExpiresIn($allowNullExpiresIn);

        $useRedirectUriOnRefreshTokenRequest = array_key_exists('use_redirect_uri_on_refresh_token_request', $data) ? $data['use_redirect_uri_on_refresh_token_request'] : false;
        $this->setUseRedirectUriOnRefreshTokenRequest($useRedirectUriOnRefreshTokenRequest);

        $appendStateToRedirectUri = array_key_exists('append_state_to_redirect_uri', $data) ? $data['append_state_to_redirect_uri'] : false;
        $this->setAppendStateToRedirectUri($appendStateToRedirectUri);

        $defaultServerScope = array_key_exists('default_server_scope', $data) ? $data['default_server_scope'] : null;
        $this->setDefaultServerScope($defaultServerScope);

        $enableDebug = array_key_exists('enable_debug', $data) ? $data['enable_debug'] : false;
        $this->setEnableDebug($enableDebug);
    }

    public function setClientId($clientId)
    {
        if (!is_string($clientId) || 0 >= strlen($clientId)) {
            throw new ClientConfigException("client_id must be a non-empty string");
        }
        $this->validateUserPass($clientId);
        $this->clientId = $clientId;
    }

    public function getClientId()
    {
        return $this->clientId;
    }

    public function setClientSecret($clientSecret)
    {
        if (null !== $clientSecret) {
            if (!is_string($clientSecret) || 0 >= strlen($clientSecret)) {
                throw new ClientConfigException("client_secret must be a non-empty string or null");
            }
            $this->validateUserPass($clientSecret);
        }
        $this->clientSecret = $clientSecret;
    }

    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    public function setAuthorizeEndpoint($authorizeEndpoint)
    {
        $this->validateEndpointUri($authorizeEndpoint);
        $this->authorizeEndpoint = $authorizeEndpoint;
    }

    public function getAuthorizeEndpoint()
    {
        return $this->authorizeEndpoint;
    }

    public function setTokenEndpoint($tokenEndpoint)
    {
        $this->validateEndpointUri($tokenEndpoint);
        $this->tokenEndpoint = $tokenEndpoint;
    }

    public function getTokenEndpoint()
    {
        return $this->tokenEndpoint;
    }

    public function setRedirectUri($redirectUri)
    {
        if (null !== $redirectUri) {
            $this->validateEndpointUri($redirectUri);
        }
        $this->redirectUri = $redirectUri;
    }

    public function getRedirectUri($state = null)
    {
        $uri = $this->redirectUri;

        if($this->getAppendStateToRedirectUri())
        {
            if(null === $state)
            {
                throw new \InvalidArgumentException('A state must be passed to getRedirectUri when the option append_state_to_redirect_uri is set to true.');
            }
            $uri_param_connector = (false === strpos($uri, '?'))?'?':'&';
            $q['redirect_uri'] = $uri.$uri_param_connector.'state='.rawurlencode($state);
            unset($q['state']);
        }

        return $uri;
    }

    public function setCredentialsInRequestBody($credentialsInRequestBody)
    {
        $this->credentialsInRequestBody = (bool) $credentialsInRequestBody;
    }

    public function getCredentialsInRequestBody()
    {
        return $this->credentialsInRequestBody;
    }

    public function setDefaultTokenType($defaultTokenType)
    {
        if (null !== $defaultTokenType) {
            if (!is_string($defaultTokenType) || 0 >= strlen($defaultTokenType)) {
                throw new ClientConfigException("default_token_type must be a non-empty string or null");
            }
        }
        $this->defaultTokenType = $defaultTokenType;
    }

    public function getDefaultTokenType()
    {
        return $this->defaultTokenType;
    }

    public function setDefaultServerScope($defaultServerScope)
    {
        if (null !== $defaultServerScope) {
            if (!is_string($defaultServerScope) || 0 >= strlen($defaultServerScope)) {
                throw new ClientConfigException("default_server_scope must be a non-empty string or null");
            }
        }
        $this->defaultServerScope = $defaultServerScope;
    }

    public function getDefaultServerScope()
    {
        return $this->defaultServerScope;
    }

    public function setAllowNullExpiresIn($allowNullExpiresIn)
    {
        $this->allowNullExpiresIn = (bool) $allowNullExpiresIn;
    }

    public function getAllowNullExpiresIn()
    {
        return $this->allowNullExpiresIn;
    }

    public function setUseRedirectUriOnRefreshTokenRequest($useRedirectUriOnRefreshTokenRequest)
    {
        $this->useRedirectUriOnRefreshTokenRequest = (bool) $useRedirectUriOnRefreshTokenRequest;
    }

    public function getUseRedirectUriOnRefreshTokenRequest()
    {
        return $this->useRedirectUriOnRefreshTokenRequest;
    }

    public function setAppendStateToRedirectUri($appendStateToRedirectUri)
    {
        $this->appendStateToRedirectUri = $appendStateToRedirectUri;
    }

    public function getAppendStateToRedirectUri()
    {
        return $this->appendStateToRedirectUri;
    }

    public function setEnableDebug($enableDebug)
    {
        $this->enableDebug = (bool) $enableDebug;
    }

    public function getEnableDebug()
    {
        return $this->enableDebug;
    }

    private function validateUserPass($userPass)
    {
        if (1 !== preg_match(self::REGEXP_VSCHAR, $userPass)) {
            throw new ClientConfigException("invalid characters in client_id or client_secret");
        }
    }

    private function validateEndpointUri($endpointUri)
    {
        if (!is_string($endpointUri) || 0 >= strlen($endpointUri)) {
            throw new ClientConfigException("uri must be a non-empty string");
        }
        if (false === filter_var($endpointUri, FILTER_VALIDATE_URL)) {
            throw new ClientConfigException("uri must be valid URL");
        }
        // not allowed to have a fragment (#) in it
        if (null !== parse_url($endpointUri, PHP_URL_FRAGMENT)) {
            throw new ClientConfigException("uri must not contain a fragment");
        }
    }
}
