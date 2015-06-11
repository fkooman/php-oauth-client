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
class Guzzle6Client implements HttpClientInterface {

    /**
     * @var \GuzzleHttp\Client
     */
    private $client;

    /**
     * @var \stdClass
     */
    private $basicAuth = null;

    public function __construct(Client $client = null)
    {
        if (null === $client) {
            $client = new Client();
        }
        $this->client = $client;
    }

    /**
     * @param string $user
     * @param string $pass
     * @return HttpClientInterface
     */
    public function setBasicAuth($user, $pass)
    {
        $this->basicAuth = [$user, $pass];
        return $this;
    }

    /**
     * @param string $url
     * @param array $postFields
     * @param array $headers
     * @return string
     */
    public function post($url, $postFields, $headers)
    {
        $post = [
            'form_params' =>$postFields,
            'headers' => []
        ];

        if (!is_array($headers)) $headers = [];
        if (!isset($headers['Content-Type'])) $headers['Content-Type'] = ['application/x-www-form-urlencoded; charset=utf-8'];

        foreach ($headers as $key=>$value) {
            if (!isset($post['headers'][$key])) $post['headers'][$key] = [];
            if (!is_array($value)) $value = [$value];
            $post['headers'][$key] += $value;
        }

        if ($this->basicAuth !== null) {
            $post['auth'] = $this->basicAuth;
        }

        $responseStream = $this->client->post($url, $post)->getBody();
        return json_decode($responseStream, true);
    }

}
