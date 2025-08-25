<?php
// filepath: /universal-lead-capture-plugin/universal-lead-capture-plugin/src/integrations/hubspot/class-hubspot.php

class HubSpotIntegration {
    private $api_key;
    private $base_url = 'https://api.hubapi.com';

    public function __construct($api_key) {
        $this->api_key = $api_key;
    }

    public function captureLead($lead_data) {
        $url = $this->base_url . '/contacts/v1/contact/?hapikey=' . $this->api_key;
        if ( ! is_array( $lead_data ) ) {
            return [ 'error' => 'invalid_data', 'message' => 'lead_data must be an array' ];
        }
        $response = $this->sendRequest($url, 'POST', $lead_data);

        if ( is_wp_error( $response ) ) {
            return [ 'error' => 'request_failed', 'message' => $response->get_error_message() ];
        }

        return $response;
    }

    private function sendRequest($url, $method, $data = null) {
        $args = [
            'method' => $method,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ];

        if ($data) {
            $args['body'] = json_encode($data);
        }

        $response = wp_remote_request($url, $args);
        return json_decode(wp_remote_retrieve_body($response), true);
    }

    public function getLeads() {
        $url = $this->base_url . '/contacts/v1/lists/all/contacts/all?hapikey=' . $this->api_key;
        return $this->sendRequest($url, 'GET');
    }
}
?>