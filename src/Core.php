<?php

namespace WooCommerceCRMPlugin;

class Core {
    protected static $instance = null;

    public static function get_instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
            self::$instance->init();
        }
        return self::$instance;
    }

    private function init() {
        $this->load_dependencies();
        $this->set_hooks();
    }

    private function load_dependencies() {
        // Load necessary files and classes
        require_once plugin_dir_path( __FILE__ ) . 'Admin/Admin.php';
        require_once plugin_dir_path( __FILE__ ) . 'Public/Public.php';
        require_once plugin_dir_path( __FILE__ ) . 'Integrations/HubSpot/HubSpotClient.php';
        require_once plugin_dir_path( __FILE__ ) . 'Integrations/Zoho/ZohoClient.php';
        require_once plugin_dir_path( __FILE__ ) . 'Forms/DynamicForm.php';
        require_once plugin_dir_path( __FILE__ ) . 'Shipping/ShippingManager.php';
        require_once plugin_dir_path( __FILE__ ) . 'Orders/OrderManager.php';
        require_once plugin_dir_path( __FILE__ ) . 'Utils/Logger.php';
    }

    private function set_hooks() {
        // Set up hooks for actions and filters
        add_action( 'init', [ $this, 'register_custom_post_types' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
    }

    public function register_custom_post_types() {
        // Register custom post types if needed
    }

    public function enqueue_scripts() {
        // Enqueue public scripts and styles with version numbers
        $version = defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : '1.0.0';
        
        wp_enqueue_style( 
            'woocommerce-crm-plugin-public', 
            plugin_dir_url( __FILE__ ) . '../assets/css/public.css',
            array(),
            $version
        );
        wp_enqueue_script( 
            'woocommerce-crm-plugin-public', 
            plugin_dir_url( __FILE__ ) . '../assets/js/public.js', 
            array( 'jquery' ), 
            $version, 
            true 
        );
    }

    public function enqueue_admin_scripts() {
        // Enqueue admin scripts and styles with version numbers
        $version = defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : '1.0.0';
        
        wp_enqueue_style( 
            'woocommerce-crm-plugin-admin', 
            plugin_dir_url( __FILE__ ) . '../assets/css/admin.css',
            array(),
            $version
        );
        wp_enqueue_script( 
            'woocommerce-crm-plugin-admin', 
            plugin_dir_url( __FILE__ ) . '../assets/js/admin.js', 
            array( 'jquery' ), 
            $version, 
            true 
        );
        
        // Localize script for AJAX with nonce
        wp_localize_script( 'woocommerce-crm-plugin-admin', 'wcp_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'wcp_admin_nonce' ),
        ) );
    }
}