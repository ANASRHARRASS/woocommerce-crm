<?php
// filepath: /universal-lead-capture-plugin/universal-lead-capture-plugin/src/api/rest-controller.php

class UniversalLeadCaptureRestController {
    private $namespace = 'ulc/v1';

    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes() {
        register_rest_route($this->namespace, '/leads', [
            'methods' => 'POST',
            'callback' => [$this, 'capture_lead'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function capture_lead(WP_REST_Request $request) {
        $data = $request->get_json_params();

        // Validate and sanitize input data
        $name = sanitize_text_field($data['name'] ?? '');
        $email = sanitize_email($data['email'] ?? '');
        $phone = sanitize_text_field($data['phone'] ?? '');

        if (empty($name) || empty($email)) {
            return new WP_Error('missing_data', 'Name and email are required.', ['status' => 400]);
        }

        // Here you would typically save the lead to the database or send it to an external service
        // For demonstration, we will just return the data
        return rest_ensure_response([
            'status' => 'success',
            'data' => [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
            ],
        ]);
    }
}
new UniversalLeadCaptureRestController();
