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

/**
 * Http Client Implementation using Guzzle 6.
 */
class Guzzle6Client implements HttpClientInterface
{
    /** @var GuzzleHttp\Client */
    private $client;

    /** @var array */
    private $headers;

    /** @var array */
    private $postParameters;

    /** @var string */
    private $basicUser;

    /** @var string */
    private $basicPass;

    public function __construct(Client $client = null)
    {
        if (null === $client) {
            $client = new Client();
        }
        $this->client = $client;
        $this->headers = array();
        $this->postParameters = array();
        $this->basicUser = null;
        $this->basicPass = null;
    }

    public function addPostFields(array $postFields)
    {
        $this->postFields = $postFields;
    }

    public function addHeader($key, $value)
    {
        $this->headers[$key] = $value;
    }

    public function setBasicAuth($user, $pass)
    {
        $this->basicUser = $user;
        $this->basicPass = $pass;
    }

    public function post($url)
    {
        $response = $this->client->post(
            $url,
            array(
                'form_params' => $this->postFields,
                'auth' => array($this->basicUser, $this->basicPass),
                'headers' => $this->headers,
            )
        );
        $responseStream = $response->getBody();

        return json_decode($responseStream, true);
    }
}
