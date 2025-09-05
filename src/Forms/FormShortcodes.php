<?php

namespace Anas\WCCRM\Forms;

defined('ABSPATH') || exit;

/**
 * Form Shortcodes Handler
 * Handles all form-related shortcodes for the CRM system
 */
class FormShortcodes
{
    private DynamicFormBuilder $form_builder;

    public function __construct()
    {
        $this->form_builder = new DynamicFormBuilder();
        $this->register_shortcodes();
        $this->register_ajax_handlers();
    }

    /**
     * Register all form shortcodes
     */
    private function register_shortcodes(): void
    {
        add_shortcode('wccrm_form', [$this, 'render_dynamic_form']);
        add_shortcode('wccrm_contact_form', [$this, 'render_contact_form']);
        add_shortcode('wccrm_lead_form', [$this, 'render_lead_form']);
        add_shortcode('wccrm_newsletter_form', [$this, 'render_newsletter_form']);
    }

    /**
     * Register AJAX handlers for form submissions
     */
    private function register_ajax_handlers(): void
    {
        add_action('wp_ajax_wccrm_submit_form', [$this, 'handle_form_submission']);
        add_action('wp_ajax_nopriv_wccrm_submit_form', [$this, 'handle_form_submission']);
        add_action('wp_ajax_wccrm_get_form_fields', [$this, 'get_dynamic_fields']);
        add_action('wp_ajax_nopriv_wccrm_get_form_fields', [$this, 'get_dynamic_fields']);
    }

    /**
     * Render dynamic form shortcode
     * [wccrm_form id="form_id" style="modern" fields="name,email,phone"]
     */
    public function render_dynamic_form($atts = []): string
    {
        $atts = shortcode_atts([
            'id' => 'default',
            'style' => 'modern',
            'fields' => 'name,email',
            'title' => '',
            'description' => '',
            'submit_text' => 'Submit',
            'redirect_url' => '',
            'progressive' => 'false',
            'conditional' => 'false',
            'hubspot_form' => '',
            'mailchimp_list' => ''
        ], $atts, 'wccrm_form');

        $fields = array_map('trim', explode(',', $atts['fields']));

        $form_config = [
            'id' => sanitize_key($atts['id']),
            'style' => sanitize_text_field($atts['style']),
            'fields' => $fields,
            'title' => sanitize_text_field($atts['title']),
            'description' => sanitize_textarea_field($atts['description']),
            'submit_text' => sanitize_text_field($atts['submit_text']),
            'redirect_url' => esc_url($atts['redirect_url']),
            'progressive_profiling' => filter_var($atts['progressive'], FILTER_VALIDATE_BOOLEAN),
            'conditional_logic' => filter_var($atts['conditional'], FILTER_VALIDATE_BOOLEAN),
            'hubspot_form_id' => sanitize_text_field($atts['hubspot_form']),
            'mailchimp_list_id' => sanitize_text_field($atts['mailchimp_list'])
        ];

        return $this->form_builder->render_form($form_config);
    }

    /**
     * Render contact form shortcode
     * [wccrm_contact_form style="hubspot"]
     */
    public function render_contact_form($atts = []): string
    {
        $atts = shortcode_atts([
            'style' => 'hubspot',
            'title' => 'Contact Us',
            'description' => 'Get in touch with our team'
        ], $atts, 'wccrm_contact_form');

        $form_config = [
            'id' => 'contact-form',
            'style' => sanitize_text_field($atts['style']),
            'fields' => ['name', 'email', 'company', 'phone', 'message'],
            'title' => sanitize_text_field($atts['title']),
            'description' => sanitize_textarea_field($atts['description']),
            'submit_text' => 'Send Message',
            'progressive_profiling' => false,
            'conditional_logic' => true
        ];

        return $this->form_builder->render_form($form_config);
    }

    /**
     * Render lead generation form shortcode
     * [wccrm_lead_form style="modern" progressive="true"]
     */
    public function render_lead_form($atts = []): string
    {
        $atts = shortcode_atts([
            'style' => 'modern',
            'title' => 'Get Started',
            'description' => 'Tell us about your business needs',
            'progressive' => 'true'
        ], $atts, 'wccrm_lead_form');

        $form_config = [
            'id' => 'lead-form',
            'style' => sanitize_text_field($atts['style']),
            'fields' => ['name', 'email', 'company', 'industry', 'budget', 'timeline'],
            'title' => sanitize_text_field($atts['title']),
            'description' => sanitize_textarea_field($atts['description']),
            'submit_text' => 'Get Started',
            'progressive_profiling' => filter_var($atts['progressive'], FILTER_VALIDATE_BOOLEAN),
            'conditional_logic' => true
        ];

        return $this->form_builder->render_form($form_config);
    }

    /**
     * Render newsletter signup form shortcode
     * [wccrm_newsletter_form style="minimal" mailchimp_list="12345"]
     */
    public function render_newsletter_form($atts = []): string
    {
        $atts = shortcode_atts([
            'style' => 'minimal',
            'title' => 'Stay Updated',
            'description' => 'Subscribe to our newsletter',
            'mailchimp_list' => ''
        ], $atts, 'wccrm_newsletter_form');

        $form_config = [
            'id' => 'newsletter-form',
            'style' => sanitize_text_field($atts['style']),
            'fields' => ['name', 'email'],
            'title' => sanitize_text_field($atts['title']),
            'description' => sanitize_textarea_field($atts['description']),
            'submit_text' => 'Subscribe',
            'progressive_profiling' => false,
            'conditional_logic' => false,
            'mailchimp_list_id' => sanitize_text_field($atts['mailchimp_list'])
        ];

        return $this->form_builder->render_form($form_config);
    }

    /**
     * Handle AJAX form submissions
     */
    public function handle_form_submission(): void
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'wccrm_form_nonce')) {
            wp_die('Security check failed', 'Unauthorized', ['response' => 401]);
        }

        try {
            $form_data = [];
            $form_id = sanitize_key($_POST['form_id'] ?? '');

            // Sanitize all form fields
            foreach ($_POST as $key => $value) {
                if (strpos($key, 'wccrm_') === 0) {
                    $field_name = str_replace('wccrm_', '', $key);
                    $form_data[$field_name] = sanitize_text_field($value);
                }
            }

            // Process the form submission
            $result = $this->form_builder->process_form_submission($form_id, $form_data);

            if ($result['success']) {
                wp_send_json_success([
                    'message' => $result['message'],
                    'redirect_url' => $result['redirect_url'] ?? '',
                    'contact_id' => $result['contact_id'] ?? null
                ]);
            } else {
                wp_send_json_error([
                    'message' => $result['message'],
                    'errors' => $result['errors'] ?? []
                ]);
            }
        } catch (\Exception $e) {
            error_log('WCCRM Form Submission Error: ' . $e->getMessage());
            wp_send_json_error(['message' => 'An unexpected error occurred. Please try again.']);
        }
    }

    /**
     * Get dynamic form fields via AJAX
     */
    public function get_dynamic_fields(): void
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'wccrm_form_nonce')) {
            wp_die('Security check failed', 'Unauthorized', ['response' => 401]);
        }

        try {
            $contact_email = sanitize_email($_POST['contact_email'] ?? '');
            $form_id = sanitize_key($_POST['form_id'] ?? '');

            if (empty($contact_email)) {
                wp_send_json_error(['message' => 'Email is required']);
                return;
            }

            // Get progressive profiling fields
            $fields = $this->form_builder->get_progressive_fields($contact_email, $form_id);

            wp_send_json_success([
                'fields' => $fields,
                'message' => 'Fields loaded successfully'
            ]);
        } catch (\Exception $e) {
            error_log('WCCRM Get Dynamic Fields Error: ' . $e->getMessage());
            wp_send_json_error(['message' => 'Failed to load dynamic fields']);
        }
    }
}
