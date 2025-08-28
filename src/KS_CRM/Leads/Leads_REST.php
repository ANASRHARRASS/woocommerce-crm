<?php
/**
 * Enhanced Leads REST endpoint with spam protection
 * Includes honeypot, timestamp delay, IP rate limiting, and optional nonce
 */

namespace KS_CRM\Leads;

use KS_CRM\Rate_Limiter\Rate_Limiter;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

defined( 'ABSPATH' ) || exit;

class Leads_REST {

    const NAMESPACE = 'kscrm/v1';
    const ENDPOINT = 'leads';
    const MIN_DELAY_SECONDS = 3;

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes(): void {
        register_rest_route( self::NAMESPACE, '/' . self::ENDPOINT, [
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'create_lead' ],
                'permission_callback' => '__return_true', // Public endpoint with custom validation
                'args'                => $this->get_endpoint_args(),
            ],
        ] );
    }

    protected function get_endpoint_args(): array {
        return [
            'email' => [
                'description' => 'Lead email address',
                'type'        => 'string',
                'format'      => 'email',
                'required'    => false,
            ],
            'phone' => [
                'description' => 'Lead phone number',
                'type'        => 'string',
                'required'    => false,
            ],
            'name' => [
                'description' => 'Lead name',
                'type'        => 'string',
                'required'    => false,
            ],
            'source' => [
                'description' => 'Lead source',
                'type'        => 'string',
                'default'     => 'api',
            ],
            'payload' => [
                'description' => 'Additional data',
                'type'        => 'object',
                'default'     => [],
            ],
            'honeypot' => [
                'description' => 'Honeypot field (should be empty)',
                'type'        => 'string',
                'default'     => '',
            ],
            'timestamp' => [
                'description' => 'Form load timestamp',
                'type'        => 'integer',
                'required'    => false,
            ],
            '_wpnonce' => [
                'description' => 'WordPress nonce (optional, enabled via filter)',
                'type'        => 'string',
                'required'    => false,
            ],
        ];
    }

    public function create_lead( WP_REST_Request $request ): WP_REST_Response {
        // 1. Rate limiting check
        if ( ! Rate_Limiter::is_allowed( 'leads', 30, 600 ) ) {
            return new WP_REST_Response( 
                [ 'error' => 'rate_limited', 'message' => 'Too many requests. Please try again later.' ], 
                429 
            );
        }

        // 2. Honeypot check
        $honeypot = sanitize_text_field( $request->get_param( 'honeypot' ) );
        if ( ! empty( $honeypot ) ) {
            // Log potential spam attempt but don't reveal why it failed
            error_log( 'KSCRM: Honeypot triggered for IP: ' . $this->get_client_ip() );
            return new WP_REST_Response( 
                [ 'error' => 'validation_failed', 'message' => 'Invalid submission.' ], 
                400 
            );
        }

        // 3. Timestamp delay check
        $timestamp = intval( $request->get_param( 'timestamp' ) );
        if ( $timestamp && ( time() - $timestamp ) < self::MIN_DELAY_SECONDS ) {
            error_log( 'KSCRM: Timestamp check failed for IP: ' . $this->get_client_ip() );
            return new WP_REST_Response( 
                [ 'error' => 'submission_too_fast', 'message' => 'Please wait before submitting.' ], 
                400 
            );
        }

        // 4. Optional nonce check (enabled via filter)
        if ( apply_filters( 'kscrm_leads_nonce_enable', false ) ) {
            $nonce = sanitize_text_field( $request->get_param( '_wpnonce' ) );
            if ( ! wp_verify_nonce( $nonce, 'kscrm_lead_submission' ) ) {
                return new WP_REST_Response( 
                    [ 'error' => 'invalid_nonce', 'message' => 'Security check failed.' ], 
                    403 
                );
            }
        }

        // 5. Validate required fields (at least email or phone)
        $email = sanitize_email( $request->get_param( 'email' ) );
        $phone = sanitize_text_field( $request->get_param( 'phone' ) );
        
        if ( empty( $email ) && empty( $phone ) ) {
            return new WP_REST_Response( 
                [ 'error' => 'missing_contact_info', 'message' => 'Email or phone number is required.' ], 
                400 
            );
        }

        // 6. Prepare lead data
        $lead_data = [
            'email'   => $email ?: null,
            'phone'   => $phone ?: null,
            'name'    => sanitize_text_field( $request->get_param( 'name' ) ) ?: null,
            'source'  => sanitize_text_field( $request->get_param( 'source' ) ) ?: 'api',
            'payload' => $request->get_param( 'payload' ) ?: [],
        ];

        // 7. Store lead (using existing lead storage mechanism)
        $lead_id = $this->store_lead( $lead_data );
        
        if ( ! $lead_id ) {
            return new WP_REST_Response( 
                [ 'error' => 'storage_failed', 'message' => 'Failed to save lead.' ], 
                500 
            );
        }

        // 8. Success response
        do_action( 'kscrm_lead_created', $lead_id, $lead_data );
        
        return new WP_REST_Response( 
            [ 'id' => $lead_id, 'message' => 'Lead created successfully.' ], 
            201 
        );
    }

    /**
     * Store lead data in database
     */
    protected function store_lead( array $lead_data ): ?int {
        global $wpdb;

        $table_name = $wpdb->prefix . 'kscrm_leads';
        
        // Check if table exists, if not use wp_leads fallback
        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name;
        if ( ! $table_exists ) {
            $table_name = $wpdb->prefix . 'wcp_leads'; // Fallback to legacy table
        }

        $insert_data = [
            'name'       => $lead_data['name'],
            'email'      => $lead_data['email'],
            'phone'      => $lead_data['phone'],
            'source'     => $lead_data['source'],
            'payload'    => wp_json_encode( $lead_data['payload'] ),
            'ip_address' => $this->get_client_ip(),
            'user_agent' => substr( $_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255 ),
            'created_at' => current_time( 'mysql' ),
        ];

        $format = [ '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ];

        $result = $wpdb->insert( $table_name, $insert_data, $format );
        
        return $result !== false ? (int) $wpdb->insert_id : null;
    }

    /**
     * Get client IP address (reused from Rate_Limiter logic)
     */
    protected function get_client_ip(): string {
        $ip = '';
        
        // Try various headers for real IP
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
                break;
            }
        }
        
        // Handle comma-separated IPs (X-Forwarded-For)
        if ( strpos( $ip, ',' ) !== false ) {
            $ip = trim( explode( ',', $ip )[0] );
        }
        
        // Validate IP
        $ip = filter_var( $ip, FILTER_VALIDATE_IP );
        return $ip ?: 'unknown';
    }
}