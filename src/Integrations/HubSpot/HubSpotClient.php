<?php

namespace WooCommerceCRMPlugin\Integrations\HubSpot;

class HubSpotClient {
    private $apiKey;
    private $baseUrl;

    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
        $this->baseUrl = 'https://api.hubapi.com';
    }

    public function createContact($data) {
        $url = $this->baseUrl . '/contacts/v1/contact/?hapikey=' . $this->apiKey;
        return $this->sendRequest('POST', $url, $data);
    }

    public function getContact($email) {
        $url = $this->baseUrl . '/contacts/v1/contact/email/' . urlencode($email) . '/profile?hapikey=' . $this->apiKey;
        return $this->sendRequest('GET', $url);
    }

    public function updateContact($email, $data) {
        $url = $this->baseUrl . '/contacts/v1/contact/email/' . urlencode($email) . '/profile?hapikey=' . $this->apiKey;
        return $this->sendRequest('POST', $url, $data);
    }

    private function sendRequest($method, $url, $data = null) {
        $options = [
            'http' => [
                'header' => "Content-Type: application/json\r\n",
                'method' => $method,
                'content' => $data ? json_encode($data) : null,
            ],
        ];
        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
        return json_decode($response, true);
    }
}