<?php

use fkooman\OAuth\Client\Api;
use fkooman\OAuth\Client\Context;
use fkooman\OAuth\Client\GoogleClientConfig;

if (file_exists('vendor/autoload.php')) require_once 'vendor/autoload.php';
if (file_exists('../../vendor/autoload.php')) require_once '../../vendor/autoload.php';

$http_client = \cdyweb\http\guzzle\Guzzle::getAdapter();

try {
    if (!file_exists('client_secrets.json')) {
        $my_url = "http://{$_SERVER['HTTP_HOST']}".preg_replace('#\?.*$#','',$_SERVER['REQUEST_URI']);
        file_put_contents('client_secrets.json',
<<<JSON
{
        "web": {
            "auth_uri": "https://accounts.google.com/o/oauth2/auth",
            "client_id": "624925808472.apps.googleusercontent.com",
            "client_secret": "HERE_USED_TO_BE_MY_SECRET",
            "token_uri": "https://accounts.google.com/o/oauth2/token",
            "redirect_uris": [$my_url]
        }
}
JSON
        );
    }
    /* OAuth client configuration */
    $clientConfig = new GoogleClientConfig(json_decode(file_get_contents('client_secrets.json'), true));

    $tokenStorage = new \fkooman\OAuth\Client\SessionStorage();

    if (isset($_GET['code'])) {
        $cb = new \fkooman\OAuth\Client\Callback('php-drive-client', $clientConfig, $tokenStorage, $http_client);
        /* handle the callback */
        $cb->handleCallback($_GET);

        $my_url = "http://{$_SERVER['HTTP_HOST']}".preg_replace('#\?.*$#','',$_SERVER['REQUEST_URI']);
        header('HTTP/1.1 302 Found');
        header("Location: {$my_url}");
        exit();
    }

    $api = new Api('php-drive-client', $clientConfig, $tokenStorage, $http_client);
    $context = new Context('john.doe@example.org', array('https://www.googleapis.com/auth/drive'));

    /* the protected endpoint uri */
    $apiUri = 'https://www.googleapis.com/drive/v2/files';

    /* get the access token */
    $accessToken = $api->getAccessToken($context);
    if (!$accessToken || isset($_GET['renew'])) {
        /* no valid access token available just yet, go to authorization server */
        header('HTTP/1.1 302 Found');
        header('Location: '.$api->getAuthorizeUri($context));
        exit;
    }

    /* we have an access token */
    try {

        $http_client->appendRequestHeaders(array(
            'Authorization'=>"Bearer {$accessToken->getAccessToken()}",
            'Accept'=>'application/json',
        ));
        $response = $http_client->get($apiUri);

        header('Content-Type: application/json');
        echo $response->getBody();
    } catch (Exception $e) {
        //@todo
        #if ('invalid_token' === ...) {
        #    /* no valid access token available just yet, go to authorization server */
        #    $api->deleteAccessToken($context);
        #    // Google does not support refresh tokens...
        #    // $api->deleteRefreshToken($context);
        #    header('HTTP/1.1 302 Found');
        #    header('Location: '.$api->getAuthorizeUri($context));
        #    exit;
        #}
        throw $e;
    }
} catch (Exception $e) {
    echo sprintf('ERROR: %s', $e->getMessage());
}
