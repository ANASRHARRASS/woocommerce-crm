<?php
/**
 * Plugin Name: WooCommerce CRM Suite
 * Plugin URI:  https://shippingsmile.com/plugins
 * Description: Unified CRM + Lead Capture + Shipping + News Intelligence + AI for WooCommerce.
 * Version:     0.2.0-dev
 * Author:      Anas Rharrass
 * License:     GPL-2.0-or-later
 * Text Domain: wccrm
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Define core constants.
 */
define( 'WCCRM_VERSION',           '0.2.0-dev' );
define( 'WCCRM_FILE',              __FILE__ );
define( 'WCCRM_DIR',               plugin_dir_path( __FILE__ ) );
define( 'WCCRM_URL',               plugin_dir_url( __FILE__ ) );
define( 'WCCRM_MIN_PHP',           '8.0' );
define( 'WCCRM_MIN_WP',            '6.0' );

/**
 * Composer Autoload (require you to run composer install).
 */
$autoload = WCCRM_DIR . 'vendor/autoload.php';
if ( file_exists( $autoload ) ) {
    require_once $autoload;
} else {
    // Graceful admin notice if dev forgot to run composer.
    add_action( 'admin_notices', function() {
        if ( current_user_can( 'manage_options' ) ) {
            echo '<div class="notice notice-error"><p><strong>WooCommerce CRM Suite:</strong> Missing vendor autoload. Run <code>composer install</code>.</p></div>';
        }
    } );
    return;
}

use Anas\WCCRM\Core\Plugin;

/**
 * Activation / Deactivation / Uninstall hooks.
 */
register_activation_hook( __FILE__, [ Plugin::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ Plugin::class, 'deactivate' ] );
// Optional: register_uninstall_hook( __FILE__, [Plugin::class, 'uninstall'] );

/**
 * Bootstrap after plugins_loaded to ensure dependencies (WooCommerce etc.) are present.
 */
add_action( 'plugins_loaded', static function() {
    Plugin::instance()->boot();
} );
