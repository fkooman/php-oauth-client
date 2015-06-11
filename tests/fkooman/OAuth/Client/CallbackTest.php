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

class CallbackTest extends \PHPUnit_Framework_TestCase
{
    /** @var array */
    private $clientConfig;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $storage;

    public function setUp()
    {
        $this->clientConfig = array();

        $this->clientConfig[] = new ClientConfig(
            array(
                'client_id' => 'foo',
                'client_secret' => 'bar',
                'authorize_endpoint' => 'http://www.example.org/authorize',
                'token_endpoint' => 'http://www.example.org/token',
            )
        );

        $this->storage = $this->getMock('\fkooman\OAuth\Client\StorageInterface');
    }

    public function testXYZ()
    {
        $client = $this->getMock('\fkooman\OAuth\Client\HttpClientInterface');

        $client->expects($this->once())
            ->method('setBasicAuth')
            ->with('foo','bar');

        $client->expects($this->once())
            ->method('post')
            ->with(
                $this->clientConfig[0]->getTokenEndpoint(),
                array('code' => 'my_code','grant_type'=>'authorization_code'),
                array('Accept' => 'application/json')
            )
            ->will($this->returnValue(array(
                'access_token' => 'my_access_token',
                'token_type' => 'BeArEr',
                'refresh_token' => 'why_not_a_refresh_token',
            )));

        $state = new State(
            array(
                'state' => 'my_state',
                'client_config_id' => 'foo',
                'issue_time' => time() - 100,
                'user_id' => 'my_user_id',
                'scope' => Scope::fromString('foo bar'),
            )
        );
        $this->storage
            ->expects($this->once())
            ->method('getState')
            ->will($this->returnValue($state));

        $callback = new Callback('foo', $this->clientConfig[0], $this->storage, $client);

        $tokenResponse = $callback->handleCallback(
            array(
                'state' => 'my_state',
                'code' => 'my_code',
            )
        );

        $this->assertEquals('my_access_token', $tokenResponse->getAccessToken());
    }
}
