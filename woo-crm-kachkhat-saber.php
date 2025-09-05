<?php
/**
 * Plugin Name: WooCommerce CRM - Kachkhat Saber
 * Description: Enhanced WooCommerce CRM with UTM tracking, analytics, lead capture, and WhatsApp integration
 * Version: 0.3.0
 * Author: Anas Rharass
 * License: GPL2
 * Requires PHP: 7.4
 * Text Domain: woocommerce-crm-plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('KSCRM_VERSION', '0.3.0');
define('KSCRM_PLUGIN_FILE', __FILE__);
define('KSCRM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('KSCRM_PLUGIN_URL', plugin_dir_url(__FILE__));

// Check PHP version
if (version_compare(PHP_VERSION, '7.4', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__('WooCommerce CRM - Kachkhat Saber requires PHP 7.4 or higher.', 'woocommerce-crm-plugin');
        echo '</p></div>';
    });
    return;
}

// Check for WooCommerce
add_action('admin_init', function() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>';
            echo esc_html__('WooCommerce CRM - Kachkhat Saber requires WooCommerce to be active.', 'woocommerce-crm-plugin');
            echo '</p></div>';
        });
        deactivate_plugins(plugin_basename(__FILE__));
        return;
    }
});

// Autoloader for new classes
spl_autoload_register(function ($class) {
    // Handle both old and new namespace structures
    if (strpos($class, 'KSCRM\\') === 0) {
        $path = KSCRM_PLUGIN_DIR . 'src/' . str_replace(['\\', 'KSCRM/'], ['/', ''], substr($class, 6)) . '.php';
        if (file_exists($path)) {
            require_once $path;
        }
    }
});

// Include existing autoloader for backward compatibility
if (file_exists(KSCRM_PLUGIN_DIR . 'woocommerce-crm.php')) {
    include_once KSCRM_PLUGIN_DIR . 'woocommerce-crm.php';
}

// Activation hook
register_activation_hook(__FILE__, 'kscrm_activate_plugin');

function kscrm_activate_plugin() {
    // Create UTM stats table
    require_once KSCRM_PLUGIN_DIR . 'src/Activation/Activator.php';
    KSCRM\Activation\Activator::activate();
}

// Initialize plugin
add_action('plugins_loaded', 'kscrm_init_plugin');

function kscrm_init_plugin() {
    // Initialize the main plugin class
    require_once KSCRM_PLUGIN_DIR . 'src/Plugin.php';
    KSCRM\Plugin::instance()->init();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'kscrm_deactivate_plugin');

function kscrm_deactivate_plugin() {
    // Clean up scheduled events
    wp_clear_scheduled_hook('kscrm_cleanup_utm_stats');
}