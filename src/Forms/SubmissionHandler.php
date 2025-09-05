<?php

namespace Anas\WCCRM\Forms;

defined('ABSPATH') || exit;

/**
 * Handles form submission processing and validation
 */
class SubmissionHandler
{

    private FormRepository $formRepository;

    public function __construct(FormRepository $formRepository)
    {
        $this->formRepository = $formRepository;
    }

    public function handle_submission(array $data): array
    {
        $form_key = sanitize_key($data['__wccrm_form_key'] ?? '');

        if (empty($form_key)) {
            return [
                'success' => false,
                'message' => 'Invalid form submission.',
            ];
        }

        $form = $this->formRepository->load_by_key($form_key);
        if (! $form || ! $form->is_active()) {
            return [
                'success' => false,
                'message' => 'Form not found or inactive.',
            ];
        }

        // Basic spam mitigation: honeypot and minimum time to submit
        if (! empty($data['__wccrm_hp'])) {
            return ['success' => true, 'message' => 'Thank you.']; // silent success
        }
        $ts = isset($data['__wccrm_ts']) ? (int)$data['__wccrm_ts'] : 0;
        if ($ts && (time() - $ts) < 2) { // <2s considered bot
            return ['success' => true, 'message' => 'Thank you.'];
        }

        // Validate submission data
        $validation_result = $this->validate_submission($form, $data);
        if (! $validation_result['valid']) {
            return [
                'success' => false,
                'message' => $validation_result['message'],
                'errors' => $validation_result['errors'],
            ];
        }

        // Sanitize and prepare submission data
        $submission_data = $this->sanitize_submission_data($form, $data);

        // Store submission
        $submission_id = $this->store_submission($form_key, $submission_data, $data);

        if ($submission_id) {
            // Fire action for other components to process
            do_action('wccrm_form_submitted', $submission_id, $submission_data);

            return [
                'success' => true,
                'message' => apply_filters('wccrm_form_success_message', 'Thank you for your submission!', $form),
                'submission_id' => $submission_id,
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to store submission. Please try again.',
            ];
        }
    }

    protected function validate_submission(FormModel $form, array $data): array
    {
        $errors = [];
        $fields = $form->get_fields();

        foreach ($fields as $field) {
            $name = $field['name'] ?? '';
            $type = $field['type'] ?? 'text';
            $required = ! empty($field['required']);
            $value = $data[$name] ?? '';

            if (empty($name)) {
                continue;
            }

            // Check required fields
            if ($required && empty($value)) {
                $label = $field['label'] ?? $name;
                $errors[$name] = sprintf('%s is required.', $label);
                continue;
            }

            // Skip validation for empty optional fields
            if (empty($value)) {
                continue;
            }

            // Type-specific validation
            switch ($type) {
                case 'email':
                    if (! is_email($value)) {
                        $errors[$name] = 'Please enter a valid email address.';
                    }
                    break;

                case 'tel':
                    // Basic phone validation (allows various formats)
                    if (! preg_match('/^[\+\-\(\)\s\d]{5,20}$/', $value)) {
                        $errors[$name] = 'Please enter a valid phone number.';
                    }
                    break;

                case 'select':
                    $options = $field['options'] ?? [];
                    $valid_values = array_column($options, 'value');
                    if (! in_array($value, $valid_values, true)) {
                        $errors[$name] = 'Please select a valid option.';
                    }
                    break;
            }
        }

        return [
            'valid' => empty($errors),
            'message' => empty($errors) ? '' : 'Please correct the errors below.',
            'errors' => $errors,
        ];
    }

    protected function sanitize_submission_data(FormModel $form, array $data): array
    {
        $sanitized = [];
        $fields = $form->get_fields();

        foreach ($fields as $field) {
            $name = $field['name'] ?? '';
            $type = $field['type'] ?? 'text';
            $value = $data[$name] ?? '';

            if (empty($name) || $type === 'hidden' || $type === 'current_product_id') {
                continue;
            }

            switch ($type) {
                case 'email':
                    $sanitized[$name] = sanitize_email($value);
                    break;

                case 'textarea':
                    $sanitized[$name] = sanitize_textarea_field($value);
                    break;

                default:
                    $sanitized[$name] = sanitize_text_field($value);
                    break;
            }
        }

        return $sanitized;
    }

    protected function store_submission(string $form_key, array $submission_data, array $raw_data): ?int
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wccrm_form_submissions';

        $insert_data = [
            'form_key' => $form_key,
            'contact_id' => null, // Will be updated by FormSubmissionLinker
            'submission_json' => wp_json_encode($submission_data),
            'user_ip' => $this->get_user_ip(),
            'user_agent' => $this->get_user_agent(),
            'created_at' => current_time('mysql'),
        ];

        $result = $wpdb->insert(
            $table_name,
            $insert_data,
            ['%s', '%d', '%s', '%s', '%s', '%s']
        );

        return $result !== false ? (int) $wpdb->insert_id : null;
    }

    protected function get_user_ip(): string
    {
        // Handle various proxy headers
        $ip_headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        ];

        foreach ($ip_headers as $header) {
            if (! empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // Handle comma-separated IPs (take first one)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                // Basic IP validation
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '';
    }

    protected function get_user_agent(): string
    {
        return substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    }
}
