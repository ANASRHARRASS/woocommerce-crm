<?php
/**
 * Honeypot spam protection for forms
 * Simple bot detection using hidden fields and timing
 */

namespace KS_CRM\Security;

defined( 'ABSPATH' ) || exit;

class Honeypot {

    private const MIN_FORM_TIME = 3; // Minimum seconds to fill form
    private const HONEYPOT_FIELD_NAME = 'website_url'; // Hidden field name
    private const TIMESTAMP_FIELD_NAME = 'form_timestamp'; // Timestamp field

    /**
     * Add honeypot fields to form HTML
     *
     * @param string $form_html Existing form HTML
     * @return string Form HTML with honeypot fields
     */
    public static function add_honeypot_fields( string $form_html ): string {
        $honeypot_html = self::get_honeypot_html();
        
        // Try to insert before closing form tag
        if ( strpos( $form_html, '</form>' ) !== false ) {
            return str_replace( '</form>', $honeypot_html . '</form>', $form_html );
        }
        
        // Fallback: append to end
        return $form_html . $honeypot_html;
    }

    /**
     * Generate honeypot HTML fields
     *
     * @return string Honeypot HTML
     */
    public static function get_honeypot_html(): string {
        $timestamp = time();
        $nonce = wp_create_nonce( 'kscrm_honeypot_' . $timestamp );
        
        return sprintf(
            '<div style="position: absolute; left: -9999px; top: -9999px; visibility: hidden;" aria-hidden="true">
                <label for="%1$s">%2$s</label>
                <input type="text" id="%1$s" name="%1$s" value="" tabindex="-1" autocomplete="off">
                <input type="hidden" name="%3$s" value="%4$s">
                <input type="hidden" name="honeypot_nonce" value="%5$s">
            </div>',
            esc_attr( self::HONEYPOT_FIELD_NAME ),
            esc_html__( 'Website URL (leave blank)', 'woocommerce-crm' ),
            esc_attr( self::TIMESTAMP_FIELD_NAME ),
            esc_attr( $timestamp ),
            esc_attr( $nonce )
        );
    }

    /**
     * Validate honeypot fields from form submission
     *
     * @param array $form_data Form submission data
     * @return array Validation result with success/error
     */
    public static function validate_submission( array $form_data ): array {
        // Check honeypot field (should be empty)
        $honeypot_value = $form_data[ self::HONEYPOT_FIELD_NAME ] ?? '';
        if ( ! empty( $honeypot_value ) ) {
            error_log( 'KSCRM: Honeypot spam detected - field filled: ' . sanitize_text_field( $honeypot_value ) );
            return [
                'success' => false,
                'error' => 'spam_detected',
                'message' => __( 'Spam submission detected.', 'woocommerce-crm' ),
                'reason' => 'honeypot_filled'
            ];
        }

        // Check form timing
        $timestamp = intval( $form_data[ self::TIMESTAMP_FIELD_NAME ] ?? 0 );
        if ( $timestamp === 0 ) {
            return [
                'success' => false,
                'error' => 'invalid_submission',
                'message' => __( 'Invalid form submission.', 'woocommerce-crm' ),
                'reason' => 'missing_timestamp'
            ];
        }

        $time_elapsed = time() - $timestamp;
        if ( $time_elapsed < self::MIN_FORM_TIME ) {
            error_log( 'KSCRM: Honeypot spam detected - form filled too quickly: ' . $time_elapsed . 's' );
            return [
                'success' => false,
                'error' => 'spam_detected',
                'message' => __( 'Form submitted too quickly. Please try again.', 'woocommerce-crm' ),
                'reason' => 'too_fast'
            ];
        }

        // Check nonce
        $nonce = $form_data['honeypot_nonce'] ?? '';
        if ( ! wp_verify_nonce( $nonce, 'kscrm_honeypot_' . $timestamp ) ) {
            return [
                'success' => false,
                'error' => 'invalid_nonce',
                'message' => __( 'Security check failed. Please refresh and try again.', 'woocommerce-crm' ),
                'reason' => 'invalid_nonce'
            ];
        }

        // Validate form age (prevent replay attacks)
        $max_age = 30 * 60; // 30 minutes
        if ( $time_elapsed > $max_age ) {
            return [
                'success' => false,
                'error' => 'form_expired',
                'message' => __( 'Form has expired. Please refresh and try again.', 'woocommerce-crm' ),
                'reason' => 'expired'
            ];
        }

        return [
            'success' => true,
            'time_elapsed' => $time_elapsed
        ];
    }

    /**
     * Validate REST API submission
     *
     * @param \WP_REST_Request $request REST request object
     * @return \WP_Error|bool WP_Error if spam detected, true if valid
     */
    public static function validate_rest_request( \WP_REST_Request $request ) {
        $form_data = $request->get_params();
        $validation = self::validate_submission( $form_data );

        if ( ! $validation['success'] ) {
            return new \WP_Error(
                $validation['error'],
                $validation['message'],
                [ 'status' => 400 ]
            );
        }

        return true;
    }

    /**
     * Add honeypot validation to existing form handlers
     *
     * @param string $hook_name Hook name to add validation to
     */
    public static function add_to_hook( string $hook_name ): void {
        add_action( $hook_name, function( $form_data ) {
            $validation = self::validate_submission( $form_data );
            if ( ! $validation['success'] ) {
                wp_die( $validation['message'], 'Spam Detection', [ 'response' => 400 ] );
            }
        }, 5 ); // Early priority
    }

    /**
     * Get honeypot statistics
     *
     * @return array Statistics about blocked submissions
     */
    public static function get_stats(): array {
        $stats = get_option( 'kscrm_honeypot_stats', [
            'total_blocked' => 0,
            'reasons' => [
                'honeypot_filled' => 0,
                'too_fast' => 0,
                'invalid_nonce' => 0,
                'expired' => 0,
                'missing_timestamp' => 0,
            ],
            'last_blocked' => 0,
        ] );

        return $stats;
    }

    /**
     * Record blocked submission
     *
     * @param string $reason Reason for blocking
     */
    public static function record_blocked_submission( string $reason ): void {
        $stats = self::get_stats();
        $stats['total_blocked']++;
        $stats['last_blocked'] = time();
        
        if ( isset( $stats['reasons'][ $reason ] ) ) {
            $stats['reasons'][ $reason ]++;
        }

        update_option( 'kscrm_honeypot_stats', $stats );
    }

    /**
     * Reset honeypot statistics
     */
    public static function reset_stats(): void {
        delete_option( 'kscrm_honeypot_stats' );
    }

    /**
     * Check if IP address is in whitelist
     *
     * @param string|null $ip IP address to check (defaults to current)
     * @return bool True if whitelisted
     */
    public static function is_ip_whitelisted( ?string $ip = null ): bool {
        if ( $ip === null ) {
            $ip = self::get_client_ip();
        }

        $whitelist = apply_filters( 'kscrm_honeypot_ip_whitelist', [
            '127.0.0.1',
            '::1',
        ] );

        return in_array( $ip, $whitelist, true );
    }

    /**
     * Get client IP address
     *
     * @return string Client IP address
     */
    private static function get_client_ip(): string {
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_REAL_IP',           // Nginx proxy
            'HTTP_X_FORWARDED_FOR',     // Load balancer/proxy
            'HTTP_X_FORWARDED',         // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP', // Cluster
            'HTTP_FORWARDED_FOR',       // Proxy
            'HTTP_FORWARDED',           // RFC 7239
            'REMOTE_ADDR'               // Standard
        ];

        foreach ( $headers as $header ) {
            if ( ! empty( $_SERVER[ $header ] ) ) {
                $ip = $_SERVER[ $header ];
                // Handle comma-separated IPs
                if ( strpos( $ip, ',' ) !== false ) {
                    $ip = trim( explode( ',', $ip )[0] );
                }
                return $ip;
            }
        }

        return '0.0.0.0';
    }

    /**
     * Enhanced validation with additional checks
     *
     * @param array $form_data Form submission data
     * @param array $options Validation options
     * @return array Validation result
     */
    public static function enhanced_validate( array $form_data, array $options = [] ): array {
        // Basic honeypot validation
        $result = self::validate_submission( $form_data );
        if ( ! $result['success'] ) {
            self::record_blocked_submission( $result['reason'] );
            return $result;
        }

        // Check IP whitelist
        if ( self::is_ip_whitelisted() ) {
            return $result;
        }

        // Additional checks can be added here:
        // - Rate limiting per IP
        // - User agent validation
        // - Referer checking
        // - Pattern matching in content

        return $result;
    }
}