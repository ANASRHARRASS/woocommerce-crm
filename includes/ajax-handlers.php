<?php
// This file handles AJAX requests for the WooCommerce CRM plugin.

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Class WC_CRM_Ajax_Handlers
 */
class WC_CRM_Ajax_Handlers {

    public function __construct() {
        add_action( 'wp_ajax_wc_crm_get_dynamic_form', array( $this, 'get_dynamic_form' ) );
        add_action( 'wp_ajax_nopriv_wc_crm_get_dynamic_form', array( $this, 'get_dynamic_form' ) );
        add_action( 'wp_ajax_wc_crm_submit_contact_form', array( $this, 'submit_contact_form' ) );
        add_action( 'wp_ajax_nopriv_wc_crm_submit_contact_form', array( $this, 'submit_contact_form' ) );
        add_action( 'wp_ajax_wc_crm_submit_reseller_form', array( $this, 'submit_reseller_form' ) );
        add_action( 'wp_ajax_nopriv_wc_crm_submit_reseller_form', array( $this, 'submit_reseller_form' ) );
    }

    public function get_dynamic_form() {
        // Logic to generate dynamic form based on WooCommerce product attributes.
        // Return the form HTML or JSON response.
        wp_send_json_success( array( 'form_html' => '<form>...</form>' ) );
    }

    public function submit_contact_form() {
        // Verify nonce for security
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wcp_admin_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed.' ), 403 );
        }

        // Validate and sanitize input
        $name = isset( $_POST['name'] ) ? sanitize_text_field( $_POST['name'] ) : '';
        $email = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
        $message = isset( $_POST['message'] ) ? sanitize_textarea_field( $_POST['message'] ) : '';

        if ( empty( $name ) || empty( $email ) ) {
            wp_send_json_error( array( 'message' => 'Name and email are required.' ), 400 );
        }

        if ( ! is_email( $email ) ) {
            wp_send_json_error( array( 'message' => 'Invalid email address.' ), 400 );
        }

        // Rate limiting check
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $transient_key = 'ajax_contact_rate_limit_' . md5( $ip );
        $attempts = get_transient( $transient_key );
        
        if ( $attempts && $attempts >= 3 ) {
            wp_send_json_error( array( 'message' => 'Too many submissions. Please try again later.' ), 429 );
        }
        
        // Increment attempt counter
        set_transient( $transient_key, ( $attempts ? $attempts : 0 ) + 1, 10 * MINUTE_IN_SECONDS );

        // Logic to handle contact form submission.
        // TODO: Implement actual form processing logic
        
        wp_send_json_success( array( 'message' => 'Contact form submitted successfully.' ) );
    }

    public function submit_reseller_form() {
        // Verify nonce for security
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wcp_admin_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed.' ), 403 );
        }

        // Check user permissions - resellers should have specific capabilities
        if ( ! current_user_can( 'read' ) ) {
            wp_send_json_error( array( 'message' => 'You do not have permission to submit this form.' ), 403 );
        }

        // Rate limiting check
        $user_id = get_current_user_id();
        $transient_key = 'reseller_form_rate_limit_' . $user_id;
        $attempts = get_transient( $transient_key );
        
        if ( $attempts && $attempts >= 2 ) {
            wp_send_json_error( array( 'message' => 'Too many submissions. Please try again later.' ), 429 );
        }
        
        // Increment attempt counter
        set_transient( $transient_key, ( $attempts ? $attempts : 0 ) + 1, 30 * MINUTE_IN_SECONDS );

        // Logic to handle reseller form submission.
        // TODO: Implement actual form processing logic
        
        wp_send_json_success( array( 'message' => 'Reseller form submitted successfully.' ) );
    }
}

new WC_CRM_Ajax_Handlers();
?>