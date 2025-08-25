<?php
/**
 * REST API endpoints for the WooCommerce CRM Plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class WooCommerce_CRM_REST_API {

    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes() {
        register_rest_route( 'woocommerce-crm/v1', '/contacts', array(
            'methods'  => 'POST',
            'callback' => array( $this, 'create_contact' ),
            'permission_callback' => array( $this, 'check_contact_permissions' ),
            'args' => array(
                'name' => array(
                    'required' => true,
                    'validate_callback' => function( $param ) {
                        return is_string( $param ) && ! empty( trim( $param ) );
                    },
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'email' => array(
                    'required' => true,
                    'validate_callback' => function( $param ) {
                        return is_email( $param );
                    },
                    'sanitize_callback' => 'sanitize_email',
                ),
            ),
        ));

        register_rest_route( 'woocommerce-crm/v1', '/orders', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'get_orders' ),
            'permission_callback' => array( $this, 'check_orders_permissions' ),
        ));

        register_rest_route( 'woocommerce-crm/v1', '/shipping-methods', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'get_shipping_methods' ),
            'permission_callback' => array( $this, 'check_shipping_permissions' ),
        ));
    }

    public function check_contact_permissions( $request ) {
        // Allow public contact creation but with rate limiting
        return true;
    }

    public function check_orders_permissions( $request ) {
        // Require proper WooCommerce permissions for orders
        return current_user_can( 'manage_woocommerce' ) || current_user_can( 'view_woocommerce_reports' );
    }

    public function check_shipping_permissions( $request ) {
        // Require proper WooCommerce permissions for shipping
        return current_user_can( 'manage_woocommerce' );
    }

    public function create_contact( $request ) {
        // Validate and sanitize input data
        $name = $request->get_param( 'name' );
        $email = $request->get_param( 'email' );
        
        if ( empty( $name ) || empty( $email ) ) {
            return new WP_Error( 'missing_required_fields', 'Name and email are required.', array( 'status' => 400 ) );
        }

        // Rate limiting check (simple implementation)
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $transient_key = 'crm_contact_rate_limit_' . md5( $ip );
        $attempts = get_transient( $transient_key );
        
        if ( $attempts && $attempts >= 5 ) {
            return new WP_Error( 'rate_limit_exceeded', 'Too many contact submissions. Please try again later.', array( 'status' => 429 ) );
        }
        
        // Increment attempt counter
        set_transient( $transient_key, ( $attempts ? $attempts : 0 ) + 1, HOUR_IN_SECONDS );

        // Logic to create a contact in HubSpot or Zoho CRM.
        // TODO: Implement actual contact creation logic
        
        return new WP_REST_Response( array(
            'message' => 'Contact created successfully',
            'data' => array(
                'name' => $name,
                'email' => $email,
            )
        ), 201 );
    }

    public function get_orders( $request ) {
        // Logic to retrieve orders from WooCommerce.
        return new WP_REST_Response( array( /* Order data */ ), 200 );
    }

    public function get_shipping_methods( $request ) {
        // Logic to retrieve shipping methods and rates.
        return new WP_REST_Response( array( /* Shipping methods data */ ), 200 );
    }
}

new WooCommerce_CRM_REST_API();
?>