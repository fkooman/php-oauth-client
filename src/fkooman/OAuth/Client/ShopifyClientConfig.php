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

use fkooman\OAuth\Client\Exception\ClientConfigException;

class ShopifyClientConfig extends ClientConfig implements ClientConfigInterface
{
    public function __construct(array $data)
    {
        if (!isset($data['shopify'])) {
            throw new ClientConfigException("no configuration 'shopify' found");
        }
        foreach (array('client_id', 'client_secret', 'shopname', 'redirect_uri') as $key) {
            if (!isset($data['shopify'][$key])) {
                throw new ClientConfigException(sprintf("missing field '%s'", $key));
            }
        }

        $clientData = array(
            'client_id' => $data['shopify']['client_id'],
            'client_secret' => $data['shopify']['client_secret'],
            'authorize_endpoint' => "https://{$data['shopify']['shopname']}.myshopify.com/admin/oauth/authorize",
            'token_endpoint' => "https://{$data['shopify']['shopname']}.myshopify.com/admin/oauth/access_token",
            'redirect_uri' => $data['shopify']['redirect_uri'],
            'credentials_in_request_body' => true,
            'use_comma_separated_scope' => true,
            'default_token_type' => 'bearer',
        );
        parent::__construct($clientData);
    }
}
