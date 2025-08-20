<?php

namespace WooCommerceCRM\Integrations\Zoho;

class ZohoClient {
    private $apiKey;
    private $apiUrl;

    public function __construct($apiKey, $apiUrl = 'https://www.zohoapis.com/crm/v2/') {
        $this->apiKey = $apiKey;
        $this->apiUrl = $apiUrl;
    }

    public function getContacts($params = []) {
        return $this->request('GET', 'contacts', $params);
    }

    public function createContact($data) {
        return $this->request('POST', 'contacts', ['data' => [$data]]);
    }

    public function updateContact($contactId, $data) {
        return $this->request('PUT', "contacts/{$contactId}", ['data' => [$data]]);
    }

    public function deleteContact($contactId) {
        return $this->request('DELETE', "contacts/{$contactId}");
    }

    private function request($method, $endpoint, $body = null) {
        $url = $this->apiUrl . $endpoint;
        $args = [
            'method' => $method,
            'headers' => [
                'Authorization' => 'Zoho-oauthtoken ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
        ];

        if ($body) {
            $args['body'] = json_encode($body);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return ['error' => $response->get_error_message()];
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }
}