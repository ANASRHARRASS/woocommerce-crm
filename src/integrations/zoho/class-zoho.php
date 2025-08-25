<?php
// filepath: /universal-lead-capture-plugin/universal-lead-capture-plugin/src/integrations/zoho/class-zoho.php

class ZohoIntegration {
    private $apiUrl;
    private $apiKey;

    public function __construct($apiUrl, $apiKey) {
        $this->apiUrl = $apiUrl;
        $this->apiKey = $apiKey;
    }

    public function captureLead($leadData) {
        $response = $this->sendRequest('/leads', 'POST', $leadData);
        return $response;
    }

    private function sendRequest($endpoint, $method, $data) {
        $url = $this->apiUrl . $endpoint;
        $args = [
            'method'    => $method,
            'headers'   => [
                'Authorization' => 'Zoho-oauthtoken ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ],
            'body'      => json_encode($data),
        ];

        $response = wp_remote_request($url, $args);
        return json_decode(wp_remote_retrieve_body($response), true);
    }

    public function getLeads($params = []) {
        $response = $this->sendRequest('/leads', 'GET', $params);
        return $response;
    }
}
?>