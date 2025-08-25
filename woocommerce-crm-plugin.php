<?php
/**
 * Plugin Name: WooCommerce CRM Plugin
 * Description: A plugin that integrates HubSpot and Zoho CRM features for WooCommerce, including dynamic forms, order management, and social media lead capture.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL2
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'WCP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WCP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include core functionalities
require_once WCP_PLUGIN_DIR . 'src/Core.php';

// Initialize the plugin
add_action( 'plugins_loaded', 'wcp_init' );

function wcp_init() {
    // Load the core class
    $core = \WooCommerceCRMPlugin\Core::get_instance();
}

// Activation and deactivation hooks
register_activation_hook( __FILE__, 'wcp_activate' );
register_deactivation_hook( __FILE__, 'wcp_deactivate' );

function wcp_activate() {
    // Code to run on activation
}

function wcp_deactivate() {
    // Code to run on deactivation
}
?>