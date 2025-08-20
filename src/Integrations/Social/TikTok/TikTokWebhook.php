<?php

namespace WooCommerceCRMPlugin\Integrations\Social\TikTok;

class TikTokWebhook {
    public function __construct() {
        // Initialize the webhook listener
        add_action('rest_api_init', [$this, 'register_webhook']);
    }

    public function register_webhook() {
        register_rest_route('woocommerce-crm-plugin/v1', '/tiktok-webhook', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_webhook'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function handle_webhook($request) {
        $data = $request->get_json_params();

        // Process the incoming TikTok data
        if (isset($data['event'])) {
            // Handle different events from TikTok
            switch ($data['event']) {
                case 'lead':
                    $this->process_lead($data['lead']);
                    break;
                // Add more cases as needed
                default:
                    return new \WP_Error('unknown_event', 'Unknown event type', ['status' => 400]);
            }
        }

        return new \WP_REST_Response('Webhook processed', 200);
    }

    private function process_lead($lead) {
        // Logic to process the lead data
        // This could involve saving the lead to the database or syncing with CRM
    }
}