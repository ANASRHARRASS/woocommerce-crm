<?php

/**
 * Plugin Name: WooCommerce CRM Plugin (Kachkhat Saber)
 * Description: Lightweight CRM integrated with WooCommerce (leads, forms, HubSpot/Zoho sync, orders, shipping, REST & shortcodes).
 * Version: 0.5.0
 * Author: Anas Rharrass
 * License: GPL2
 * Requires PHP: 8.0
 * Requires at least: 5.0
 * Tested up to: 6.3
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

// Prevent direct access
defined('ABSPATH') || exit;

// Constants
define('WCCRM_VERSION', '0.5.0');
define('WCCRM_PLUGIN_FILE', __FILE__);
define('WCCRM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WCCRM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('KSCRM_CACHE_DEFAULT_TTL', 3600); // 1 hour default cache TTL
define('WCCRM_ALLOW_PUBLIC_INGEST', false);

function wccrm_emergency_menu_registration()
{
    // Emergency registration to ensure menu always appears
    if (!current_user_can('manage_options')) {
        return;
    }

    // Simple registration without duplication checking for now
    add_menu_page(
        'CRM Dashboard',
        'CRM',
        'manage_options',
        'crm-dashboard-emergency',
        'wccrm_emergency_dashboard',
        'dashicons-chart-line',
        25
    );
}

function wccrm_emergency_dashboard()
{
?>
    <div class="wrap">
        <h1>🚀 CRM Dashboard (Emergency Access)</h1>
        <div class="notice notice-success">
            <p><strong>Success!</strong> The CRM menu is now visible in your WordPress admin.</p>
        </div>

        <div class="card">
            <h2>Menu Status</h2>
            <p>✅ Emergency menu registration is working</p>
            <p>✅ You have administrator access</p>
            <p>✅ Plugin is active and running</p>
        </div>

        <div class="card">
            <h2>Quick Access</h2>
            <p>Since you can see this page, the CRM menu is working properly.</p>
            <p><a href="<?php echo admin_url('admin.php?page=crm-dashboard'); ?>" class="button button-primary">Try Main Dashboard</a></p>
        </div>
    </div>
    <style>
        .card {
            background: #fff;
            border: 1px solid #ccd0d4;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
        }
    </style>
<?php
}

function wccrm_force_register_menu()
{
    // Force register the menu immediately
    add_menu_page(
        'CRM Dashboard',
        'CRM',
        'manage_options',
        'crm-dashboard-immediate',
        'wccrm_immediate_dashboard',
        'dashicons-chart-line',
        25
    );
}

function wccrm_immediate_dashboard()
{
?>
    <div class="wrap">
        <h1>🎉 CRM Dashboard - SUCCESS!</h1>
        <div class="notice notice-success">
            <p><strong>Great!</strong> The CRM menu is now visible and working!</p>
        </div>

        <div class="card" style="max-width: none;">
            <h2>Dashboard Status</h2>
            <table class="wp-list-table widefat fixed striped">
                <tbody>
                    <tr>
                        <td><strong>Plugin Status</strong></td>
                        <td>✅ Active and Running</td>
                    </tr>
                    <tr>
                        <td><strong>Admin Menu</strong></td>
                        <td>✅ Successfully Registered</td>
                    </tr>
                    <tr>
                        <td><strong>User Permissions</strong></td>
                        <td><?php echo current_user_can('manage_options') ? '✅ Administrator Access' : '❌ Insufficient Permissions'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>WordPress Version</strong></td>
                        <td><?php echo get_bloginfo('version'); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Plugin Version</strong></td>
                        <td><?php echo WCCRM_VERSION; ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="card" style="max-width: none;">
            <h2>Quick Actions</h2>
            <p>
                <a href="<?php echo admin_url('admin.php?page=crm-dashboard-immediate'); ?>" class="button button-primary">Refresh Dashboard</a>
                <a href="<?php echo admin_url('plugins.php'); ?>" class="button">Manage Plugins</a>
                <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
                    <a href="<?php echo admin_url('admin.php?page=crm-debug'); ?>" class="button">Debug Tools</a>
                <?php endif; ?>
            </p>
        </div>

        <div class="card" style="max-width: none;">
            <h2>Next Steps</h2>
            <ol>
                <li><strong>Menu is Working:</strong> You can now see the CRM menu in your admin sidebar</li>
                <li><strong>Configure Settings:</strong> Set up your CRM preferences and integrations</li>
                <li><strong>Add Contacts:</strong> Start managing your customer relationships</li>
                <li><strong>Monitor Performance:</strong> Use the built-in analytics and reporting tools</li>
            </ol>
        </div>
    </div>
    <style>
        .card {
            background: #fff;
            border: 1px solid #ccd0d4;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
        }

        .wp-list-table td {
            padding: 10px;
        }

        .notice {
            margin: 20px 0;
        }
    </style>
<?php
}

// Check PHP version early
if (version_compare(\PHP_VERSION, '8.0', '<')) {
    add_action('admin_notices', 'wccrm_php_version_notice');
    return;
}

function wccrm_php_version_notice()
{
    echo '<div class="notice notice-error"><p>';
    echo esc_html__('WooCommerce CRM requires PHP 8.0 or higher. Please update your PHP version.', 'woocommerce-crm');
    echo '</p></div>';
}

// Autoloader setup
if (file_exists(WCCRM_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once WCCRM_PLUGIN_DIR . 'vendor/autoload.php';
}

// PSR-4 autoloader for our namespace
spl_autoload_register(function ($class) {
    if (strpos($class, 'Anas\\WCCRM\\') === 0) {
        $relative_class = substr($class, 11); // Remove 'Anas\WCCRM\'

        // Try src/ directory first (new structure)
        $path = WCCRM_PLUGIN_DIR . 'src/' . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($path)) {
            require_once $path;
            return;
        }

        // Try legacy directories
        $legacy_paths = [
            WCCRM_PLUGIN_DIR . 'admin/' . str_replace('\\', '/', $relative_class) . '.php',
            WCCRM_PLUGIN_DIR . 'includes/' . str_replace('\\', '/', $relative_class) . '.php'
        ];

        foreach ($legacy_paths as $legacy_path) {
            if (file_exists($legacy_path)) {
                require_once $legacy_path;
                return;
            }
        }
    }
});

// Direct admin menu registration function
function wccrm_register_admin_menu()
{
    // Only register if user has proper capabilities
    if (!current_user_can('manage_options')) {
        return;
    }

    // Main CRM menu page
    add_menu_page(
        'CRM Dashboard',
        'CRM',
        'manage_options',
        'crm-dashboard',
        'wccrm_render_dashboard',
        'dashicons-chart-line',
        25
    );

    // Settings submenu
    add_submenu_page(
        'crm-dashboard',
        'CRM Settings',
        'Settings',
        'manage_options',
        'crm-settings',
        'wccrm_render_settings'
    );

    // Debug submenu (only in debug mode)
    if (defined('WP_DEBUG') && WP_DEBUG) {
        add_submenu_page(
            'crm-dashboard',
            'CRM Debug',
            'Debug',
            'manage_options',
            'crm-debug',
            'wccrm_render_debug'
        );
    }
}

// Dashboard render function
function wccrm_render_dashboard()
{
?>
    <div class="wrap">
        <h1>CRM Dashboard</h1>
        <div class="notice notice-success">
            <p><strong>Success!</strong> The CRM dashboard is now working correctly.</p>
        </div>

        <div class="card">
            <h2>Quick Stats</h2>
            <p>Welcome to your WooCommerce CRM dashboard. This is a simplified version to ensure the menu is visible.</p>

            <h3>Plugin Status</h3>
            <ul>
                <li>✅ Plugin Active</li>
                <li>✅ Admin Menu Registered</li>
                <li>✅ User Has Required Permissions</li>
            </ul>

            <h3>Available Features</h3>
            <ul>
                <li><a href="<?php echo admin_url('admin.php?page=crm-settings'); ?>">Settings</a></li>
                <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
                    <li><a href="<?php echo admin_url('admin.php?page=crm-debug'); ?>">Debug Tools</a></li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="card">
            <h2>Next Steps</h2>
            <p>Now that the dashboard is visible, you can:</p>
            <ol>
                <li>Configure your CRM settings</li>
                <li>Import/export contact data</li>
                <li>Set up integrations</li>
                <li>Monitor performance</li>
            </ol>
        </div>
    </div>
<?php
}

// Settings render function
function wccrm_render_settings()
{
?>
    <div class="wrap">
        <h1>CRM Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('wccrm_settings');
            do_settings_sections('wccrm_settings');
            ?>

            <table class="form-table">
                <tr>
                    <th scope="row">API Key</th>
                    <td>
                        <input type="text" name="wccrm_api_key" value="<?php echo esc_attr(get_option('wcp_api_key', '')); ?>" class="regular-text" readonly />
                        <p class="description">Your CRM API key for integrations.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Enable Debugging</th>
                    <td>
                        <label>
                            <input type="checkbox" <?php echo defined('WP_DEBUG') && WP_DEBUG ? 'checked disabled' : ''; ?> />
                            Debug mode (controlled by WP_DEBUG constant)
                        </label>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Debug render function
function wccrm_render_debug()
{
    if (class_exists('\Anas\WCCRM\Debug\DashboardDebug')) {
        \Anas\WCCRM\Debug\DashboardDebug::render_debug_page();
    } else {
    ?>
        <div class="wrap">
            <h1>CRM Debug</h1>
            <div class="notice notice-info">
                <p>Debug class not available. Basic debug information:</p>
            </div>

            <h2>Plugin Information</h2>
            <table class="wp-list-table widefat fixed striped">
                <tr>
                    <td>Plugin Version</td>
                    <td><?php echo defined('WCCRM_VERSION') ? WCCRM_VERSION : 'Unknown'; ?></td>
                </tr>
                <tr>
                    <td>Plugin Directory</td>
                    <td><?php echo defined('WCCRM_PLUGIN_DIR') ? WCCRM_PLUGIN_DIR : 'Unknown'; ?></td>
                </tr>
                <tr>
                    <td>WordPress Debug</td>
                    <td><?php echo defined('WP_DEBUG') && WP_DEBUG ? 'Enabled' : 'Disabled'; ?></td>
                </tr>
                <tr>
                    <td>Current User ID</td>
                    <td><?php echo get_current_user_id(); ?></td>
                </tr>
                <tr>
                    <td>User Can Manage Options</td>
                    <td><?php echo current_user_can('manage_options') ? 'Yes' : 'No'; ?></td>
                </tr>
            </table>
        </div>
<?php
    }
}

// Initialize the plugin
add_action('plugins_loaded', 'wccrm_init', 20);

function wccrm_init()
{
    // Check for WooCommerce
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-warning"><p>';
            echo esc_html__('WooCommerce CRM: WooCommerce not active – some features may be limited.', 'woocommerce-crm');
            echo '</p></div>';
        });
    }

    // Initialize the main plugin with optimized loading
    try {
        // Ensure admin menu is always registered (fallback)
        if (is_admin()) {
            add_action('admin_menu', 'wccrm_register_admin_menu');
        }

        // Try optimized loader first
        if (class_exists('\Anas\WCCRM\Core\OptimizedLoader')) {
            $loader = \Anas\WCCRM\Core\OptimizedLoader::instance();
            $loader->init();
        }
        // Use the main Plugin class if available
        elseif (class_exists('\Anas\WCCRM\Core\Plugin')) {
            $plugin = \Anas\WCCRM\Core\Plugin::instance();
            $plugin->init();
        }
        // Fallback to legacy implementation
        else {
            require_once WCCRM_PLUGIN_DIR . 'includes/Core.php';
            new \Anas\WCCRM\Core();
        }

        // Initialize advanced features after main plugin loads
        if (class_exists('\Anas\WCCRM\Integration\NextStepsIntegrator')) {
            \Anas\WCCRM\Integration\NextStepsIntegrator::instance();
        }

        // Initialize Morocco Enhanced CRM features
        if (class_exists('\Anas\WCCRM\Integration\MoroccoEnhancedCRM')) {
            \Anas\WCCRM\Integration\MoroccoEnhancedCRM::instance()->init();
        }

        // Trigger action to indicate plugin is fully loaded
        do_action('wccrm_after_init');
    } catch (\Exception $e) {
        add_action('admin_notices', function () use ($e) {
            echo '<div class="notice notice-error"><p>';
            echo esc_html__('WooCommerce CRM: Initialization failed: ', 'woocommerce-crm') . esc_html($e->getMessage());
            echo '</p></div>';
        });
        error_log('WCCRM: Initialization failed - ' . $e->getMessage());
    }
}

// Activation hook
register_activation_hook(__FILE__, 'wccrm_activate');

function wccrm_activate()
{
    // Generate API key for backward compatibility
    if (!get_option('wcp_api_key')) {
        add_option('wcp_api_key', bin2hex(random_bytes(16)));
    }

    // Run database migrations
    try {
        if (class_exists('\Anas\WCCRM\Database\Installer')) {
            $installer = new \Anas\WCCRM\Database\Installer();
            $installer->maybe_upgrade();
        }

        // Initialize advanced features tables
        if (class_exists('\Anas\WCCRM\Security\RateLimiter')) {
            \Anas\WCCRM\Security\RateLimiter::create_violations_table();
        }

        if (class_exists('\Anas\WCCRM\Database\IndexOptimizer')) {
            \Anas\WCCRM\Database\IndexOptimizer::create_indexes();
        }
    } catch (\Exception $e) {
        error_log('WCCRM: Activation failed - ' . $e->getMessage());
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            esc_html__('WooCommerce CRM activation failed: ', 'woocommerce-crm') . esc_html($e->getMessage()),
            esc_html__('Plugin Activation Error', 'woocommerce-crm'),
            ['back_link' => true]
        );
    }
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'wccrm_deactivate');

function wccrm_deactivate()
{
    // Clear any scheduled events (existing)
    wp_clear_scheduled_hook('wccrm_cleanup_old_interests');
    wp_clear_scheduled_hook('kscrm_daily_retention_cleanup');
    wp_clear_scheduled_hook('wccrm_dispatch_outbound_queue');
    wp_clear_scheduled_hook('wccrm_data_retention_cycle');
    wp_clear_scheduled_hook('wccrm_cod_verification_expiry_check');

    // Clear advanced feature scheduled events
    wp_clear_scheduled_hook('wccrm_daily_optimization');
    wp_clear_scheduled_hook('wccrm_performance_log');
    wp_clear_scheduled_hook('wccrm_cache_warmup');
    wp_clear_scheduled_hook('wccrm_database_optimize');
    wp_clear_scheduled_hook('wccrm_process_tasks');

    // Clean up transients
    delete_transient('wccrm_news_');

    // Clear any object cache
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
}

// Utility functions for logging
if (!function_exists('wcp_log')) {
    function wcp_log($msg, $context = [])
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[WCCRM] ' . $msg . ($context ? ' ' . wp_json_encode($context) : ''));
        }
    }
}

// Backward compatibility functions
if (!function_exists('wccrm_get_plugin')) {
    function wccrm_get_plugin()
    {
        if (class_exists('\Anas\WCCRM\Core\Plugin')) {
            return \Anas\WCCRM\Core\Plugin::instance();
        }
        return null;
    }
}

// Legacy function deprecation notices
if (!function_exists('wcp_init')) {
    function wcp_init()
    {
        _deprecated_function(__FUNCTION__, '2.0.0', 'wccrm_init');
        wccrm_init();
    }
}
