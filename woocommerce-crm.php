<?php
/**
 * Plugin Name: WooCommerce CRM Plugin
 * Description: Lightweight CRM integrated with WooCommerce (leads, forms, HubSpot/Zoho sync, orders, shipping, REST & shortcodes).
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL2
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) exit;

// Constants
define( 'WCP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WCP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WCP_VERSION', '1.0.0' );

// Composer autoload (if present)
if ( file_exists( WCP_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
    require_once WCP_PLUGIN_DIR . 'vendor/autoload.php';
}

// Fallback PSR-4 autoloader (WCP\*)
spl_autoload_register( function ( $class ) {
    if ( strpos( $class, 'WCP\\' ) === 0 ) {
        $path = WCP_PLUGIN_DIR . 'src/' . str_replace( '\\', '/', substr( $class, 4 ) ) . '.php';
        if ( file_exists( $path ) ) require_once $path;
    }
} );

// Include Core explicitly (ensures fatal early if missing)
require_once WCP_PLUGIN_DIR . 'src/Core.php';

// Initialize Core
add_action( 'plugins_loaded', 'wcp_init' );
function wcp_init() {
    if ( class_exists( 'WCP\Core' ) ) {
        if ( ! class_exists( 'WooCommerce' ) ) {
            add_action( 'admin_notices', function () {
                echo '<div class="notice notice-warning"><p>WooCommerce CRM: WooCommerce not active – order sync disabled.</p></div>';
            } );
        }
        ( new WCP\Core() )->init();
    } else {
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-error"><p>WooCommerce CRM: Core class missing.</p></div>';
        } );
    }
}

// Admin settings + leads page bootstrap
add_action( 'plugins_loaded', function () {
    if ( is_admin() ) {
        if ( class_exists( 'WCP\\Admin\\Settings' ) ) WCP\Admin\Settings::init();
        if ( class_exists( 'WCP\\Admin\\LeadsPage' ) ) WCP\Admin\LeadsPage::init();
    }
} );

// Plugin action links
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function ( $links ) {
    $links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=wcp-settings' ) ) . '">Settings</a>';
    $links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=wcp-leads' ) ) . '">Leads</a>';
    return $links;
} );

// Activation / Deactivation
register_activation_hook( __FILE__, 'wcp_activate' );
register_deactivation_hook( __FILE__, 'wcp_deactivate' );

function wcp_activate() {
    global $wpdb;
    $table   = $wpdb->prefix . 'wcp_leads';
    $charset = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE {$table} (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        source VARCHAR(50) NOT NULL,
        email VARCHAR(190) NULL,
        phone VARCHAR(50) NULL,
        name VARCHAR(190) NULL,
        payload LONGTEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX email (email),
        INDEX phone (phone),
        INDEX source (source),
        INDEX created_at (created_at)
    ) $charset;";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );

    add_option( 'wcp_version', WCP_VERSION );
    if ( ! get_option( 'wcp_api_key' ) ) {
        add_option( 'wcp_api_key', bin2hex( random_bytes( 16 ) ) );
    }
}

function wcp_deactivate() {
    // Placeholder: clear cron events when added.
}

// Simple logger helper (debug)
if ( ! function_exists( 'wcp_log' ) ) {
    function wcp_log( $msg, $context = [] ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[WCP] ' . $msg . ( $context ? ' ' . wp_json_encode( $context ) : '' ) );
        }
    }
}

// NOTE: Removed secondary duplicate bootstrap (universal-lead-capture-plugin.php) during cleanup.
?>