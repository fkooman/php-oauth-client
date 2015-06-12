<?php

namespace fkooman\OAuth\Client;

class SimpleStorage implements \fkooman\OAuth\Client\StorageInterface {
    public function storeAccessToken(\fkooman\OAuth\Client\AccessToken $accessToken) {
        file_put_contents('access.token',serialize($accessToken));
    }

    public function getAccessToken($clientConfigId, \fkooman\OAuth\Client\Context $context) {
        if (!file_exists('access.token')) return false;
        return unserialize(file_get_contents('access.token'));
    }

    public function deleteAccessToken(\fkooman\OAuth\Client\AccessToken $accessToken) {
        if (file_exists('access.token')) unlink('access.token');
    }

    public function storeState(\fkooman\OAuth\Client\State $state) {
        file_put_contents('access.state',serialize($state));
    }

    public function getState($clientConfigId, $state) {
        if (!file_exists('access.state')) return false;
        return unserialize(file_get_contents('access.state'));
    }

    public function deleteState(\fkooman\OAuth\Client\State $state) {
        if (file_exists('access.state')) unlink('access.state');
    }

    public function deleteStateForContext($clientConfigId, \fkooman\OAuth\Client\Context $context) {
        if (file_exists('access.state')) unlink('access.state');
    }

    public function storeRefreshToken(\fkooman\OAuth\Client\RefreshToken $refreshToken) {
        file_put_contents('refresh.token',serialize($refreshToken));
    }

    public function getRefreshToken($clientConfigId, \fkooman\OAuth\Client\Context $context) {
        if (!file_exists('refresh.token')) return false;
        return unserialize(file_get_contents('refresh.token'));
    }

    public function deleteRefreshToken(\fkooman\OAuth\Client\RefreshToken $refreshToken) {
        if (file_exists('refresh.token')) unlink('refresh.token');
    }

}
