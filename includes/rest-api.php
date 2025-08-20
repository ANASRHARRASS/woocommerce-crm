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
            'permission_callback' => '__return_true',
        ));

        register_rest_route( 'woocommerce-crm/v1', '/orders', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'get_orders' ),
            'permission_callback' => '__return_true',
        ));

        register_rest_route( 'woocommerce-crm/v1', '/shipping-methods', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'get_shipping_methods' ),
            'permission_callback' => '__return_true',
        ));
    }

    public function create_contact( $request ) {
        // Logic to create a contact in HubSpot or Zoho CRM.
        return new WP_REST_Response( 'Contact created successfully', 201 );
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