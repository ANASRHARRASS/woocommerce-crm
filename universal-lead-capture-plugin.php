<?php
/**
 * Plugin Name: Universal Lead Capture Plugin
 * Description: A comprehensive lead capture plugin that integrates with various platforms, including HubSpot, Zoho, WooCommerce, and advertising networks.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL2
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'ULCP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ULCP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include the bootstrap file
require_once ULCP_PLUGIN_DIR . 'src/bootstrap.php';

// Activation hook
function ulcp_activate() {
    // Code to run on plugin activation
}
register_activation_hook( __FILE__, 'ulcp_activate' );

// Deactivation hook
function ulcp_deactivate() {
    // Code to run on plugin deactivation
}
register_deactivation_hook( __FILE__, 'ulcp_deactivate' );

// Initialize the plugin
add_action( 'plugins_loaded', 'ulcp_init' );

function ulcp_init() {
    // Load necessary components and integrations
    // Example: new ElementorIntegration();
    // Example: new WooCommerceIntegration();
    // Add more integrations as needed
}
?>