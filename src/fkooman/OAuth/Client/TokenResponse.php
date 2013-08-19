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

class TokenResponse
{
    const DEFAULT_TOKEN_TYPE = "bearer";
    private $accessToken;
    private $tokenType;
    private $expiresIn;
    private $refreshToken;
    private $scope;

    public function __construct($accessToken, $tokenType)
    {
        $this->setAccessToken($accessToken);
        $this->setTokenType($tokenType);
        $this->setExpiresIn(null);
        $this->setRefreshToken(null);
        $this->setScope(null);
    }

    public static function fromArray(array $data)
    {
        foreach (array('access_token') as $key) {
            if (!array_key_exists($key, $data)) {
                throw new TokenResponseException(sprintf("missing field '%s'", $key));
            }
        }
        $data['token_type'] = array_key_exists('token_type', $data) ? $data['token_type'] : self::DEFAULT_TOKEN_TYPE;
        $t = new static($data['access_token'], $data['token_type']);
        if (array_key_exists('expires_in', $data)) {
            $t->setExpiresIn($data['expires_in']);
        }
        if (array_key_exists('refresh_token', $data)) {
            $t->setRefreshToken($data['refresh_token']);
        }
        if (array_key_exists('scope', $data)) {
            $t->setScope($data['scope']);
        }

        return $t;
    }

    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    public function getAccessToken()
    {
        return $this->accessToken;
    }

    public function setTokenType($tokenType)
    {
        $this->tokenType = $tokenType;
    }

    public function getTokenType()
    {
        return $this->tokenType;
    }

    public function setExpiresIn($expiresIn)
    {
        $this->expiresIn = $expiresIn;
    }

    public function getExpiresIn()
    {
        return $this->expiresIn;
    }

    public function setRefreshToken($refreshToken)
    {
        $this->refreshToken = $refreshToken;
    }

    public function getRefreshToken()
    {
        return $this->refreshToken;
    }

    public function setScope($scope)
    {
        $this->scope = $scope;
    }

    public function getScope()
    {
        return $this->scope;
    }
}
