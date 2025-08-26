<?php
/**
 * Plugin Name: WooCommerce CRM Plugin
 * Description: Lightweight CRM integrated with WooCommerce (leads, forms, HubSpot/Zoho sync, orders, shipping, REST & shortcodes).
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

// (Optional) Composer autoload if dependencies installed.
if ( file_exists( WCP_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
    require_once WCP_PLUGIN_DIR . 'vendor/autoload.php';
}

// Include core functionalities
require_once WCP_PLUGIN_DIR . 'src/Core.php';

// Initialize the plugin
add_action( 'plugins_loaded', 'wcp_init' );

function wcp_init() {
    if ( class_exists( 'WCP\Core' ) ) {
        // WooCommerce soft check (still allow limited operation without).
        if ( ! class_exists( 'WooCommerce' ) ) {
            add_action( 'admin_notices', function () {
                echo '<div class="notice notice-warning"><p>WooCommerce CRM: WooCommerce not active – order sync disabled.</p></div>';
            } );
        }
        $core = new WCP\Core();
        $core->init();
    } else {
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-error"><p>WooCommerce CRM: Core class missing.</p></div>';
        } );
    }
}

// Admin settings bootstrap (UI).
add_action( 'plugins_loaded', function () {
    if ( is_admin() ) {
        // Fallback include if composer autoload not present yet.
        if ( ! class_exists( '\WCP\Admin\Settings' ) && file_exists( WCP_PLUGIN_DIR . 'src/Admin/Settings.php' ) ) {
            require_once WCP_PLUGIN_DIR . 'src/Admin/Settings.php';
        }
        if ( class_exists( '\WCP\Admin\Settings' ) ) {
            \WCP\Admin\Settings::init();
        }
    }
} );

// Add Settings link on plugins list.
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function ( $links ) {
    $url = admin_url( 'admin.php?page=wcp-settings' );
    $links[] = '<a href="' . esc_url( $url ) . '">Settings</a>';
    return $links;
} );

// Activation and deactivation hooks
register_activation_hook( __FILE__, 'wcp_activate' );
register_deactivation_hook( __FILE__, 'wcp_deactivate' );

function wcp_activate() {
    // TODO: create required options / DB tables with version flag.
    global $wpdb;
    $table = $wpdb->prefix . 'wcp_leads';
    $charset = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS {$table} (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        source VARCHAR(50) NOT NULL,
        email VARCHAR(190) NULL,
        phone VARCHAR(50) NULL,
        name VARCHAR(190) NULL,
        payload LONGTEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX (email),
        INDEX (phone),
        INDEX (source)
    ) {$charset};";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
    add_option( 'wcp_version', '1.0.0' );
}

function wcp_deactivate() {
    // TODO: scheduled events cleanup if added later.
}

// NOTE: Removed secondary duplicate bootstrap (universal-lead-capture-plugin.php) during cleanup.
?>