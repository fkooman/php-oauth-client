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

use cdyweb\http\psr\Response;
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

        $this->tokenResponse[] =
            array(
                'access_token' => 'foo',
                'token_type' => 'Bearer',
            )
        ;

        $this->tokenResponse[] = '{';

        $this->tokenResponse[] =
            array(
                'access_token' => 'foo',
                'token_type' => 'Bearer',
                'expires_in' => '1200',
            )
        ;

        $this->tokenResponse[] =
            array(
                'access_token' => 'foo',
                'token_type' => 'Bearer',
                'scope' => array('foo', 'bar'),
            )
        ;

        $this->tokenResponse[] =
            array(
                'access_token' => 'foo',
                'token_type' => 'Bearer',
                'scope' => 'foo,bar',
            )
        ;
    }

    public function testWithAuthorizationCode()
    {
        $client = $this->getMock('\cdyweb\http\Adapter');

        $client->expects($this->once())
            ->method('setBasicAuth')
            ->with('foo','bar');

        $client->expects($this->once())
            ->method('post')
            ->with(
                $this->clientConfig[0]->getTokenEndpoint(),
                array('Accept' => 'application/json'),
                array('code' => '12345','grant_type'=>'authorization_code')
            )
            ->will($this->returnValue(new Response(200,array(),json_encode($this->tokenResponse[0]))));

        $tokenRequest = new TokenRequest($client, $this->clientConfig[0]);
        $result = $tokenRequest->withAuthorizationCode('12345');

        $this->assertInstanceOf('\fkooman\OAuth\Client\TokenResponse', $result);
    }

    public function testWithAuthorizationCodeCredentialsInRequestBody()
    {
        $client = $this->getMock('\cdyweb\http\Adapter');

        $client->expects($this->never())
            ->method('setBasicAuth');

        $client->expects($this->once())
            ->method('post')
            ->with(
                $this->clientConfig[1]->getTokenEndpoint(),
                array('Accept' => 'application/json'),
                array('code' => '12345','grant_type'=>'authorization_code','redirect_uri'=>'http://foo.example.org/callback','client_id'=>'foo','client_secret'=>'bar')
            )
            ->will($this->returnValue(new Response(200,array(),json_encode($this->tokenResponse[0]))));

        $tokenRequest = new TokenRequest($client, $this->clientConfig[1]);
        $result = $tokenRequest->withAuthorizationCode('12345');

        $this->assertInstanceOf('\fkooman\OAuth\Client\TokenResponse', $result);
    }

    public function testAllowStringExpiresIn()
    {
        $client = $this->getMock('\cdyweb\http\Adapter');

        $client->expects($this->once())
            ->method('post')
            ->with(
                $this->clientConfig[2]->getTokenEndpoint(),
                array('Accept' => 'application/json'),
                array('code' => '12345','grant_type'=>'authorization_code','redirect_uri'=>'http://foo.example.org/callback')
            )
            ->will($this->returnValue(new Response(200,array(),json_encode($this->tokenResponse[2]))));

        $tokenRequest = new TokenRequest($client, $this->clientConfig[2]);
        $tokenResponse = $tokenRequest->withAuthorizationCode('12345');
        $this->assertEquals(1200, $tokenResponse->getExpiresIn());
    }

    public function testAllowArrayScope()
    {
        $client = $this->getMock('\cdyweb\http\Adapter');

        $client->expects($this->once())
            ->method('post')
            ->with(
                $this->clientConfig[3]->getTokenEndpoint(),
                array('Accept' => 'application/json'),
                array('code' => '12345','grant_type'=>'authorization_code','redirect_uri'=>'http://foo.example.org/callback')
            )
            ->will($this->returnValue(new Response(200,array(),json_encode($this->tokenResponse[3]))));

        $tokenRequest = new TokenRequest($client, $this->clientConfig[3]);
        $tokenResponse = $tokenRequest->withAuthorizationCode('12345');
        $this->assertTrue($tokenResponse->getScope()->equals(Scope::fromString('foo bar')));
    }

    public function testAllowCommaSeparatedScope()
    {
        $client = $this->getMock('\cdyweb\http\Adapter');

        $client->expects($this->once())
            ->method('post')
            ->with(
                $this->clientConfig[4]->getTokenEndpoint(),
                array('Accept' => 'application/json'),
                array('code' => '12345','grant_type'=>'authorization_code','redirect_uri'=>'http://foo.example.org/callback')
            )
            ->will($this->returnValue(new Response(200,array(),json_encode($this->tokenResponse[4]))));

        $tokenRequest = new TokenRequest($client, $this->clientConfig[4]);
        $tokenResponse = $tokenRequest->withAuthorizationCode('12345');
        $this->assertTrue($tokenResponse->getScope()->equals(Scope::fromString('foo bar')));
    }

    public function testWithRefreshToken()
    {
        $client = $this->getMock('\cdyweb\http\Adapter');
        $client->expects($this->once())
            ->method('post')
            ->with(
                $this->clientConfig[4]->getTokenEndpoint(),
                array('Accept' => 'application/json'),
                array('refresh_token' => 'refresh_123_456','grant_type'=>'refresh_token')
            )
            ->will($this->returnValue(new Response(200,array(),json_encode($this->tokenResponse[0]))));

        $tokenRequest = new TokenRequest($client, $this->clientConfig[0]);
        $result = $tokenRequest->withRefreshToken('refresh_123_456');

        $this->assertInstanceOf('\fkooman\OAuth\Client\TokenResponse', $result);
    }

    public function testBrokenJsonResponse()
    {
        $client = $this->getMock('\cdyweb\http\Adapter');

        $client->expects($this->once())
            ->method('post')
            ->with(
                $this->clientConfig[0]->getTokenEndpoint(),
                array('Accept' => 'application/json'),
                array('code' => '12345','grant_type'=>'authorization_code')
            )
            ->willThrowException(new \fkooman\OAuth\Client\Exception\TokenResponseException());

        $tokenRequest = new TokenRequest($client, $this->clientConfig[0]);

        $this->setExpectedException('\fkooman\OAuth\Client\Exception\TokenResponseException');
        $result = $tokenRequest->withAuthorizationCode('12345');

        $this->assertFalse($result);
    }
}
