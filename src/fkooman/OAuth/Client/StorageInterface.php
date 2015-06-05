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

interface StorageInterface
{
    /**
     * @param AccessToken $accessToken
     */
    public function storeAccessToken(AccessToken $accessToken);

    /**
     * @param $clientConfigId
     * @param Context $context
     * @return AccessToken
     */
    public function getAccessToken($clientConfigId, Context $context);

    /**
     * @param AccessToken $accessToken
     */
    public function deleteAccessToken(AccessToken $accessToken);

    /**
     * @param RefreshToken $refreshToken
     */
    public function storeRefreshToken(RefreshToken $refreshToken);

    /**
     * @param $clientConfigId
     * @param Context $context
     * @return RefreshToken
     */
    public function getRefreshToken($clientConfigId, Context $context);

    /**
     * @param RefreshToken $refreshToken
     */
    public function deleteRefreshToken(RefreshToken $refreshToken);

    /**
     * @param State $state
     */
    public function storeState(State $state);

    /**
     * @param $clientConfigId
     * @param $state
     * @return State
     */
    public function getState($clientConfigId, $state);

    /**
     * @param State $state
     */
    public function deleteState(State $state);

    /**
     * @param $clientConfigId
     * @param Context $context
     */
    public function deleteStateForContext($clientConfigId, Context $context);
}
