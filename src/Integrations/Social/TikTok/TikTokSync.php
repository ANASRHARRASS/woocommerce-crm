<?php

namespace WooCommerceCRMPlugin\Integrations\Social\TikTok;

class TikTokSync {
    private $apiUrl = 'https://api.tiktok.com/';
    private $accessToken;

    public function __construct($accessToken) {
        $this->accessToken = $accessToken;
    }

    public function syncLead($leadData) {
        $response = $this->sendRequest('leads/sync', $leadData);
        return $response;
    }

    private function sendRequest($endpoint, $data) {
        $url = $this->apiUrl . $endpoint;

        $args = [
            'method' => 'POST',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($data),
        ];

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => $response->get_error_message(),
            ];
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }
}