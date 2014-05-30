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

use fkooman\OAuth\Common\Scope;
use PDO;

class PdoStorage implements StorageInterface
{
    /** @var PDO */
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getAccessToken($clientConfigId, Context $context)
    {
        $stmt = $this->db->prepare("SELECT * FROM access_tokens WHERE client_config_id = :client_config_id AND user_id = :user_id AND scope = :scope");
        $stmt->bindValue(":client_config_id", $clientConfigId, PDO::PARAM_STR);
        $stmt->bindValue(":user_id", $context->getUserId(), PDO::PARAM_STR);
        $stmt->bindValue(":scope", $context->getScope()->toString(), PDO::PARAM_STR);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if (false !== $result) {
            $result['scope'] = Scope::fromString($result['scope']);

            return new AccessToken($result);
        }

        return false;
    }

    public function storeAccessToken(AccessToken $accessToken)
    {
        $stmt = $this->db->prepare("INSERT INTO access_tokens (client_config_id, user_id, scope, access_token, token_type, expires_in, issue_time) VALUES(:client_config_id, :user_id, :scope, :access_token, :token_type, :expires_in, :issue_time)");
        $stmt->bindValue(":client_config_id", $accessToken->getClientConfigId(), PDO::PARAM_STR);
        $stmt->bindValue(":user_id", $accessToken->getUserId(), PDO::PARAM_STR);
        $stmt->bindValue(":scope", $accessToken->getScope()->toString(), PDO::PARAM_STR);
        $stmt->bindValue(":access_token", $accessToken->getAccessToken(), PDO::PARAM_STR);
        $stmt->bindValue(":token_type", $accessToken->getTokenType(), PDO::PARAM_STR);
        $stmt->bindValue(":expires_in", $accessToken->getExpiresIn(), PDO::PARAM_INT);
        $stmt->bindValue(":issue_time", $accessToken->getIssueTime(), PDO::PARAM_INT);

        $stmt->execute();

        return 1 === $stmt->rowCount();
    }

    public function deleteAccessToken(AccessToken $accessToken)
    {
        $stmt = $this->db->prepare("DELETE FROM access_tokens WHERE client_config_id = :client_config_id AND user_id = :user_id AND access_token = :access_token");
        $stmt->bindValue(":client_config_id", $accessToken->getClientConfigId(), PDO::PARAM_STR);
        $stmt->bindValue(":user_id", $accessToken->getUserId(), PDO::PARAM_STR);
        $stmt->bindValue(":access_token", $accessToken->getAccessToken(), PDO::PARAM_STR);
        $stmt->execute();

        return 1 === $stmt->rowCount();
    }

    public function getRefreshToken($clientConfigId, Context $context)
    {
        $stmt = $this->db->prepare("SELECT * FROM refresh_tokens WHERE client_config_id = :client_config_id AND user_id = :user_id AND scope = :scope");
        $stmt->bindValue(":client_config_id", $clientConfigId, PDO::PARAM_STR);
        $stmt->bindValue(":user_id", $context->getUserId(), PDO::PARAM_STR);
        $stmt->bindValue(":scope", $context->getScope()->toString(), PDO::PARAM_STR);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if (false !== $result) {
            $result['scope'] = Scope::fromString($result['scope']);

            return new RefreshToken($result);
        }

        return false;
    }

    public function storeRefreshToken(RefreshToken $refreshToken)
    {
        $stmt = $this->db->prepare("INSERT INTO refresh_tokens (client_config_id, user_id, scope, refresh_token, issue_time) VALUES(:client_config_id, :user_id, :scope, :refresh_token, :issue_time)");
        $stmt->bindValue(":client_config_id", $refreshToken->getClientConfigId(), PDO::PARAM_STR);
        $stmt->bindValue(":user_id", $refreshToken->getUserId(), PDO::PARAM_STR);
        $stmt->bindValue(":scope", $refreshToken->getScope()->toString(), PDO::PARAM_STR);
        $stmt->bindValue(":refresh_token", $refreshToken->getRefreshToken(), PDO::PARAM_STR);
        $stmt->bindValue(":issue_time", $refreshToken->getIssueTime(), PDO::PARAM_INT);

        $stmt->execute();

        return 1 === $stmt->rowCount();
    }

    public function deleteRefreshToken(RefreshToken $refreshToken)
    {
        $stmt = $this->db->prepare("DELETE FROM refresh_tokens WHERE client_config_id = :client_config_id AND user_id = :user_id AND refresh_token = :refresh_token");
        $stmt->bindValue(":client_config_id", $refreshToken->getClientConfigId(), PDO::PARAM_STR);
        $stmt->bindValue(":user_id", $refreshToken->getUserId(), PDO::PARAM_STR);
        $stmt->bindValue(":refresh_token", $refreshToken->getRefreshToken(), PDO::PARAM_STR);
        $stmt->execute();

        return 1 === $stmt->rowCount();
    }

    public function getState($clientConfigId, $state)
    {
        $stmt = $this->db->prepare("SELECT * FROM states WHERE client_config_id = :client_config_id AND state = :state");
        $stmt->bindValue(":client_config_id", $clientConfigId, PDO::PARAM_STR);
        $stmt->bindValue(":state", $state, PDO::PARAM_STR);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if (false !== $result) {
            $result['scope'] = Scope::fromString($result['scope']);

            return new State($result);
        }

        return false;
    }

    public function storeState(State $state)
    {
        $stmt = $this->db->prepare("INSERT INTO states (client_config_id, user_id, scope, issue_time, state) VALUES(:client_config_id, :user_id, :scope, :issue_time, :state)");
        $stmt->bindValue(":client_config_id", $state->getClientConfigId(), PDO::PARAM_STR);
        $stmt->bindValue(":user_id", $state->getUserId(), PDO::PARAM_STR);
        $stmt->bindValue(":scope", $state->getScope()->toString(), PDO::PARAM_STR);
        $stmt->bindValue(":issue_time", $state->getIssueTime(), PDO::PARAM_INT);
        $stmt->bindValue(":state", $state->getState(), PDO::PARAM_STR);
        $stmt->execute();

        return 1 === $stmt->rowCount();
    }

    public function deleteStateForContext($clientConfigId, Context $context)
    {
        $stmt = $this->db->prepare("DELETE FROM states WHERE client_config_id = :client_config_id AND user_id = :user_id");
        $stmt->bindValue(":client_config_id", $clientConfigId, PDO::PARAM_STR);
        $stmt->bindValue(":user_id", $context->getUserId(), PDO::PARAM_STR);
        $stmt->execute();

        return 1 === $stmt->rowCount();
    }

    public function deleteState(State $state)
    {
        $stmt = $this->db->prepare("DELETE FROM states WHERE client_config_id = :client_config_id AND state = :state");
        $stmt->bindValue(":client_config_id", $state->getClientConfigId(), PDO::PARAM_STR);
        $stmt->bindValue(":state", $state->getState(), PDO::PARAM_STR);
        $stmt->execute();

        return 1 === $stmt->rowCount();
    }
}
