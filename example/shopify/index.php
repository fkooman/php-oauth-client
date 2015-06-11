<?php

if (isset($_GET['code'])) {
    require 'callback.php';
    return;
}

if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
}
if (file_exists('../../vendor/autoload.php')) {
    require_once '../../vendor/autoload.php';
}

use fkooman\OAuth\Client\Api;
use fkooman\OAuth\Client\Context;
use fkooman\OAuth\Client\ShopifyClientConfig;
use fkooman\OAuth\Client\SessionStorage;
use GuzzleHttp\Client;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;

function add_header($tokenStorage, $context)
{
    return function (callable $handler) use ($tokenStorage, $context) {
        return function (
            \GuzzleHttp\Psr7\Request $request,
            array $options
        ) use ($handler, $tokenStorage, $context) {
            $token = $tokenStorage->getAccessToken('php-shopify-client', $context)->getAccessToken();
            $request = $request
                ->withHeader('X-Shopify-Access-Token', $token)
                ->withHeader('Accept', 'application/json');
            return $handler($request, $options);
        };
    };
}

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

    $stack = new HandlerStack();
    $stack->setHandler(new CurlHandler());
    $stack->push(add_header($tokenStorage, $context));
    $client = new Client([
            'verify' => false,
            'handler' => $stack,
    ]);
    $api = new Api('php-shopify-client', $clientConfig, $tokenStorage, new \fkooman\OAuth\Client\Guzzle6Client($client));

    /* the protected endpoint uri */
    $apiUri = 'https://'.$shopname.'.myshopify.com/admin/pages';

    /* get the access token */
    $accessToken = $api->getAccessToken($context);
    if (!$accessToken) {
        /* no valid access token available just yet, go to authorization server */
        header('HTTP/1.1 302 Found');
        header('Location: '.$api->getAuthorizeUri($context));
        exit;
    }

    /* we have an access token */
    $response = $client->get($apiUri);

    var_dump(json_decode($response->getBody(),true));

} catch (Exception $e) {
    echo sprintf('ERROR: %s', $e->getMessage());
}
