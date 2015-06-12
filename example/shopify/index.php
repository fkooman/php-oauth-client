<?php

if (file_exists('vendor/autoload.php')) require_once 'vendor/autoload.php';
if (file_exists('../../vendor/autoload.php')) require_once '../../vendor/autoload.php';

$http_client = \cdyweb\http\guzzle\Guzzle::getAdapter();

use fkooman\OAuth\Client\Api;
use fkooman\OAuth\Client\Context;
use fkooman\OAuth\Client\ShopifyClientConfig;
use fkooman\OAuth\Client\SessionStorage;

try {
    if (!file_exists('client_secrets.json')) {
        file_put_contents('client_secrets.json',
<<<JSON
{
  "shopify": {
    "client_id": "YOUR ID",
    "client_secret": "YOUR SECRET",
    "shopname": "YOUR SHOP NAME",
    "redirect_uri": "http://localhost/",
    "permissions": [
        "read_content",
        "write_content",
        "read_themes",
        "write_themes",
        "read_products",
        "write_products",
        "read_customers",
        "write_customers",
        "read_orders",
        "write_orders",
        "read_script_tags",
        "write_script_tags",
        "read_fulfillments",
        "write_fulfillments",
        "read_shipping",
        "write_shipping"
    ]
  }
}
JSON
);
    }
    /* OAuth client configuration */
    $settings=json_decode(file_get_contents('client_secrets.json'), true);
    $clientConfig = new ShopifyClientConfig($settings);
    $shopname = $settings['shopify']['shopname'];
    $context = new Context($shopname, $settings['shopify']['permissions']);

    //$db = new PDO(sprintf("sqlite:%s/data/client.sqlite", __DIR__));
    //$tokenStorage = new PdoStorage($db);
    $tokenStorage = new SessionStorage();

    if (isset($_GET['code'])) {
        /* load token from session */
        $tokenStorage = new SessionStorage();

        /* initialize the Callback */
        $cb = new \fkooman\OAuth\Client\Callback('php-shopify-client', $clientConfig, $tokenStorage, $http_client);

        /* handle the callback */
        $cb->handleCallback($_GET);

        /* redirect to main script */
        header('HTTP/1.1 302 Found');
        header('Location: '.$clientConfig->getRedirectUri());
        exit();
    }

    $api = new Api('php-shopify-client', $clientConfig, $tokenStorage, $http_client);

    /* the protected endpoint uri */
    $apiUri = 'https://'.$shopname.'.myshopify.com/admin/pages';

    /* get the access token */
    $accessToken = $api->getAccessToken($context);
    if (!$accessToken || isset($_GET['renew'])) {
        /* no valid access token available just yet, go to authorization server */
        header('HTTP/1.1 302 Found');
        header('Location: '.$api->getAuthorizeUri($context));
        exit;
    }

    /* we have an access token */
    $http_client->appendRequestHeaders(array(
        'X-Shopify-Access-Token'=>$accessToken->getAccessToken(),
        'Accept'=>'application/json'
    ));

    $response = $http_client->get($apiUri);

    var_dump(json_decode($response->getBody(),true));

} catch (Exception $e) {
    echo sprintf('ERROR: %s', $e->getMessage());
}
