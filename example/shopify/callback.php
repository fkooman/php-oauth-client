<?php

if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
}
if (file_exists('../../vendor/autoload.php')) {
    require_once '../../vendor/autoload.php';
}

use fkooman\OAuth\Client\Callback;
use fkooman\OAuth\Client\SessionStorage;
use fkooman\OAuth\Client\ShopifyClientConfig;
use GuzzleHttp\Client;

/* OAuth client configuration */
$clientConfig = new ShopifyClientConfig(json_decode(file_get_contents('client_secrets.json'), true));

#try {
    //$db = new PDO(sprintf("sqlite:%s/data/client.sqlite", __DIR__));
    //$tokenStorage = new PdoStorage($db);
    $tokenStorage = new SessionStorage();

    /* initialize the Callback */
    $client = new Client([
            'verify' => false,
    ]);
    $cb = new Callback('php-shopify-client', $clientConfig, $tokenStorage, $client);
    /* handle the callback */
    $cb->handleCallback($_GET);

    header('HTTP/1.1 302 Found');
    header('Location: '.$clientConfig->getRedirectUri());
#} catch (Exception $e) {
#    echo sprintf('ERROR: %s', $e->getMessage());
#}
