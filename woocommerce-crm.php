<?php
/**
 * Plugin Name: WooCommerce CRM Plugin (Kachkhat Saber)
 * Description: Lightweight CRM integrated with WooCommerce (leads, forms, HubSpot/Zoho sync, orders, shipping, REST & shortcodes).
 * Version: 0.5.0
 * Author: Your Name
 * License: GPL2
 * Requires PHP: 8.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) exit;

// Constants
define( 'WCCRM_VERSION', '0.5.0' );
define( 'KSCRM_CACHE_DEFAULT_TTL', 3600 ); // 1 hour default cache TTL
define( 'WCCRM_PLUGIN_FILE', __FILE__ );
define( 'WCCRM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WCCRM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Check PHP version
if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
    add_action( 'admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__( 'WooCommerce CRM requires PHP 8.0 or higher. Please update your PHP version.', 'woocommerce-crm' );
        echo '</p></div>';
    } );
    return;
}

// Composer autoload (if present)
if ( file_exists( WCCRM_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
    require_once WCCRM_PLUGIN_DIR . 'vendor/autoload.php';
}

// PSR-4 autoloader for our namespace
spl_autoload_register( function ( $class ) {
    if ( strpos( $class, 'Anas\\WCCRM\\' ) === 0 ) {
        $path = WCCRM_PLUGIN_DIR . 'src/' . str_replace( [ '\\', 'Anas/WCCRM/' ], [ '/', '' ], substr( $class, 11 ) ) . '.php';
        if ( file_exists( $path ) ) {
            require_once $path;
        }
    }
} );

// Initialize the plugin
add_action( 'plugins_loaded', 'wccrm_init' );

function wccrm_init() {
    // Check for WooCommerce
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-warning"><p>';
            echo esc_html__( 'WooCommerce CRM: WooCommerce not active – some features may be limited.', 'woocommerce-crm' );
            echo '</p></div>';
        } );
    }

    // Initialize the main plugin
    try {
        $plugin = \Anas\WCCRM\Core\Plugin::instance();
        $plugin->init();
    } catch ( \Exception $e ) {
        add_action( 'admin_notices', function () use ( $e ) {
            echo '<div class="notice notice-error"><p>';
            echo esc_html__( 'WooCommerce CRM: Initialization failed: ', 'woocommerce-crm' ) . esc_html( $e->getMessage() );
            echo '</p></div>';
        } );
        error_log( 'WCCRM: Initialization failed - ' . $e->getMessage() );
    }
}

// Activation hook
register_activation_hook( __FILE__, 'wccrm_activate' );

function wccrm_activate() {
    // Generate API key for backward compatibility
    if ( ! get_option( 'wcp_api_key' ) ) {
        add_option( 'wcp_api_key', bin2hex( random_bytes( 16 ) ) );
    }

    // Run database migrations
    try {
        $installer = new \Anas\WCCRM\Database\Installer();
        $installer->maybe_upgrade();
    } catch ( \Exception $e ) {
        error_log( 'WCCRM: Activation failed - ' . $e->getMessage() );
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die( 
            esc_html__( 'WooCommerce CRM activation failed: ', 'woocommerce-crm' ) . esc_html( $e->getMessage() ),
            esc_html__( 'Plugin Activation Error', 'woocommerce-crm' ),
            [ 'back_link' => true ]
        );
    }
}

// Deactivation hook
register_deactivation_hook( __FILE__, 'wccrm_deactivate' );

function wccrm_deactivate() {
    // Clear any scheduled events
    wp_clear_scheduled_hook( 'wccrm_cleanup_old_interests' );
    wp_clear_scheduled_hook( 'kscrm_daily_retention_cleanup' );
    
    // Clean up transients
    delete_transient( 'wccrm_news_' );
}

// Utility function for logging (backward compatibility)
if ( ! function_exists( 'wcp_log' ) ) {
    function wcp_log( $msg, $context = [] ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[WCCRM] ' . $msg . ( $context ? ' ' . wp_json_encode( $context ) : '' ) );
        }
    }
}

// Backward compatibility shims
if ( ! function_exists( 'wccrm_get_plugin' ) ) {
    function wccrm_get_plugin() {
        return \Anas\WCCRM\Core\Plugin::instance();
    }
}

// Legacy function deprecation notices
if ( ! function_exists( 'wcp_init' ) ) {
    function wcp_init() {
        _deprecated_function( __FUNCTION__, '2.0.0', 'wccrm_init' );
        wccrm_init();
    }
}