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

use GuzzleHttp\Client;
use GuzzleHttp\Middleware;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use fkooman\OAuth\Common\Scope;

class TokenRequestTest extends \PHPUnit_Framework_TestCase
{
    /** @var array */
    private $clientConfig;

    /** @var array */
    private $tokenResponse;

    public function setUp()
    {
        $this->clientConfig = array();
        $this->tokenResponse = array();

        $this->clientConfig[] = new ClientConfig(
            array(
                'client_id' => 'foo',
                'client_secret' => 'bar',
                'authorize_endpoint' => 'http://www.example.org/authorize',
                'token_endpoint' => 'http://www.example.org/token',
            )
        );

        $this->clientConfig[] = new ClientConfig(
            array(
                'client_id' => 'foo',
                'client_secret' => 'bar',
                'authorize_endpoint' => 'http://www.example.org/authorize',
                'token_endpoint' => 'http://www.example.org/token',
                'redirect_uri' => 'http://foo.example.org/callback',
                'credentials_in_request_body' => true,
            )
        );

        $this->clientConfig[] = new ClientConfig(
            array(
                'client_id' => 'foo',
                'client_secret' => 'bar',
                'authorize_endpoint' => 'http://www.example.org/authorize',
                'token_endpoint' => 'http://www.example.org/token',
                'redirect_uri' => 'http://foo.example.org/callback',
                'allow_string_expires_in' => true,
            )
        );

        $this->clientConfig[] = new ClientConfig(
            array(
                'client_id' => 'foo',
                'client_secret' => 'bar',
                'authorize_endpoint' => 'http://www.example.org/authorize',
                'token_endpoint' => 'http://www.example.org/token',
                'redirect_uri' => 'http://foo.example.org/callback',
                'use_array_scope' => true,
            )
        );

        $this->clientConfig[] = new ClientConfig(
            array(
                'client_id' => 'foo',
                'client_secret' => 'bar',
                'authorize_endpoint' => 'http://www.example.org/authorize',
                'token_endpoint' => 'http://www.example.org/token',
                'redirect_uri' => 'http://foo.example.org/callback',
                'use_comma_separated_scope' => true,
            )
        );

        $this->tokenResponse[] = json_encode(
            array(
                'access_token' => 'foo',
                'token_type' => 'Bearer',
            )
        );

        $this->tokenResponse[] = '{';

        $this->tokenResponse[] = json_encode(
            array(
                'access_token' => 'foo',
                'token_type' => 'Bearer',
                'expires_in' => '1200',
            )
        );

        $this->tokenResponse[] = json_encode(
            array(
                'access_token' => 'foo',
                'token_type' => 'Bearer',
                'scope' => array('foo', 'bar'),
            )
        );

        $this->tokenResponse[] = json_encode(
            array(
                'access_token' => 'foo',
                'token_type' => 'Bearer',
                'scope' => 'foo,bar',
            )
        );
    }

    public function testWithAuthorizationCode()
    {
        $mock = new MockHandler([new Response(200, [], \GuzzleHttp\Psr7\stream_for($this->tokenResponse[0]))]);
        $container = [];
        $history = Middleware::history($container);
        $stack = \GuzzleHttp\HandlerStack::create($mock);
        $stack->push($history);
        $client = new Client(['handler' => $stack]);

        $tokenRequest = new TokenRequest($client, $this->clientConfig[0]);
        $tokenRequest->withAuthorizationCode('12345');

        $lastRequest = array_pop($container);
        $this->assertEquals('POST', $lastRequest['request']->getMethod());
        $this->assertEquals('code=12345&grant_type=authorization_code', $lastRequest['request']->getBody()->__toString());
        $this->assertEquals('Basic Zm9vOmJhcg==', $lastRequest['request']->getHeaderLine('Authorization'));
        $this->assertEquals(
            'application/x-www-form-urlencoded; charset=utf-8',
            $lastRequest['request']->getHeaderLine('Content-Type')
        );
    }

    public function testWithAuthorizationCodeCredentialsInRequestBody()
    {
        $mock = new MockHandler([new Response(200, [], \GuzzleHttp\Psr7\stream_for($this->tokenResponse[0]))]);
        $container = [];
        $history = Middleware::history($container);
        $stack = \GuzzleHttp\HandlerStack::create($mock);
        $stack->push($history);
        $client = new Client(['handler' => $stack]);

        $tokenRequest = new TokenRequest($client, $this->clientConfig[1]);
        $tokenRequest->withAuthorizationCode('12345');
        $lastRequest = array_pop($container);
        $this->assertEquals('POST', $lastRequest['request']->getMethod());
        $this->assertEquals(
            'code=12345&grant_type=authorization_code&redirect_uri=http%3A%2F%2Ffoo.example.org%2Fcallback&client_id=foo&client_secret=bar',
            $lastRequest['request']->getBody()->__toString()
        );
        $this->assertEquals(
            'application/x-www-form-urlencoded; charset=utf-8',
            $lastRequest['request']->getHeaderLine('Content-Type')
        );
    }

    public function testAllowStringExpiresIn()
    {
        $mock = new MockHandler([new Response(200, [], \GuzzleHttp\Psr7\stream_for($this->tokenResponse[2]))]);
        $container = [];
        $history = Middleware::history($container);
        $stack = \GuzzleHttp\HandlerStack::create($mock);
        $stack->push($history);
        $client = new Client(['handler' => $stack]);

        $tokenRequest = new TokenRequest($client, $this->clientConfig[2]);
        $tokenResponse = $tokenRequest->withAuthorizationCode('12345');
        $this->assertEquals(1200, $tokenResponse->getExpiresIn());
    }

    public function testAllowArrayScope()
    {
        $mock = new MockHandler([new Response(200, [], \GuzzleHttp\Psr7\stream_for($this->tokenResponse[3]))]);
        $container = [];
        $history = Middleware::history($container);
        $stack = \GuzzleHttp\HandlerStack::create($mock);
        $stack->push($history);
        $client = new Client(['handler' => $stack]);

        $tokenRequest = new TokenRequest($client, $this->clientConfig[3]);
        $tokenResponse = $tokenRequest->withAuthorizationCode('12345');
        $this->assertTrue($tokenResponse->getScope()->equals(Scope::fromString('foo bar')));
    }

    public function testAllowCommaSeparatedScope()
    {
        $mock = new MockHandler([new Response(200, [], \GuzzleHttp\Psr7\stream_for($this->tokenResponse[4]))]);
        $container = [];
        $history = Middleware::history($container);
        $stack = \GuzzleHttp\HandlerStack::create($mock);
        $stack->push($history);
        $client = new Client(['handler' => $stack]);

        $tokenRequest = new TokenRequest($client, $this->clientConfig[4]);
        $tokenResponse = $tokenRequest->withAuthorizationCode('12345');
        $this->assertTrue($tokenResponse->getScope()->equals(Scope::fromString('foo bar')));
    }

    public function testWithRefreshToken()
    {
        $mock = new MockHandler([new Response(200, [], \GuzzleHttp\Psr7\stream_for($this->tokenResponse[0]))]);
        $container = [];
        $history = Middleware::history($container);
        $stack = \GuzzleHttp\HandlerStack::create($mock);
        $stack->push($history);
        $client = new Client(['handler' => $stack]);

        $tokenRequest = new TokenRequest($client, $this->clientConfig[0]);
        $tokenRequest->withRefreshToken('refresh_123_456');

        $lastRequest = array_pop($container);
        $this->assertEquals('POST', $lastRequest['request']->getMethod());
        $this->assertEquals('Basic Zm9vOmJhcg==', $lastRequest['request']->getHeaderLine('Authorization'));
        $this->assertEquals(
            'refresh_token=refresh_123_456&grant_type=refresh_token',
            $lastRequest['request']->getBody()->__toString()
        );
        $this->assertEquals(
            'application/x-www-form-urlencoded; charset=utf-8',
            $lastRequest['request']->getHeaderLine('Content-Type')
        );
    }

    public function testBrokenJsonResponse()
    {
        $mock = new MockHandler([new Response(200, [], \GuzzleHttp\Psr7\stream_for($this->tokenResponse[1]))]);
        $container = [];
        $history = Middleware::history($container);
        $stack = \GuzzleHttp\HandlerStack::create($mock);
        $stack->push($history);
        $client = new Client(['handler' => $stack]);

        $tokenRequest = new TokenRequest($client, $this->clientConfig[0]);
        $this->setExpectedException('\fkooman\OAuth\Client\Exception\TokenResponseException');
        $this->assertFalse($tokenRequest->withRefreshToken('refresh_123_456'));
    }
}
