<?php
/**
 * Bootstrap the Universal Lead Capture Plugin.
 *
 * This file is responsible for loading necessary components and initializing the plugin's functionality.
 */

// Define the plugin directory (point to plugin root, parent of src/)
if ( ! defined( 'ULCP_PLUGIN_DIR' ) ) {
    define( 'ULCP_PLUGIN_DIR', dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR );
}

// Include necessary files
require_once ULCP_PLUGIN_DIR . 'src/integrations/class-integration-manager.php';
require_once ULCP_PLUGIN_DIR . 'src/integrations/elementor/class-elementor.php';
require_once ULCP_PLUGIN_DIR . 'src/integrations/woocommerce/class-woocommerce.php';
require_once ULCP_PLUGIN_DIR . 'src/integrations/hubspot/class-hubspot.php';
require_once ULCP_PLUGIN_DIR . 'src/integrations/zoho/class-zoho.php';
require_once ULCP_PLUGIN_DIR . 'src/integrations/ads/class-google-ads.php';
require_once ULCP_PLUGIN_DIR . 'src/integrations/ads/class-facebook-ads.php';
require_once ULCP_PLUGIN_DIR . 'src/admin/class-admin.php';
require_once ULCP_PLUGIN_DIR . 'src/public/class-public-controller.php';
require_once ULCP_PLUGIN_DIR . 'src/forms/class-form-builder.php';
require_once ULCP_PLUGIN_DIR . 'src/forms/class-dynamic-form.php';
require_once ULCP_PLUGIN_DIR . 'src/api/rest-controller.php';
require_once ULCP_PLUGIN_DIR . 'src/utils/class-logger.php';

// Initialize the plugin
function ulc_initialize_plugin() {
    // Initialize integrations only when properly configured (do not call constructors that require args here)

    // Safe to instantiate WooCommerceIntegration (constructor takes no args)
    if ( class_exists( 'WooCommerceIntegration' ) ) {
        new WooCommerceIntegration();
    } else {
        error_log( 'ULC: WooCommerceIntegration class not found.' );
    }

    // Admin and public classes (instantiate the actual class names present in the codebase)
    if ( class_exists( 'Admin' ) ) {
        new Admin();
    } else {
        error_log( 'ULC: Admin class not found.' );
    }

    // The public-facing class in the repo is now Public_Controller
    if ( class_exists( 'Public_Controller' ) ) {
        new Public_Controller();
    } else {
        error_log( 'ULC: Public_Controller class not found.' );
    }

    // FormBuilder is safe to instantiate (optional constructor)
    if ( class_exists( 'FormBuilder' ) ) {
        new FormBuilder();
    }

    // Do not auto-instantiate integrations that require credentials or config.
    // Use admin settings (get_option) to obtain configuration and instantiate later.
    $settings = get_option( 'universal_lead_capture_settings', [] );

    // Register factories for lazy instantiation via IntegrationManager
    if ( class_exists( 'IntegrationManager' ) ) {
        // Elementor expects plugin name
        IntegrationManager::register( 'elementor', function() use ( $settings ) {
            if ( empty( $settings['elementor_plugin_name'] ) ) {
                error_log( 'ULC: Elementor not configured (elementor_plugin_name).' );
                return null;
            }
            if ( class_exists( 'ElementorIntegration' ) ) {
                return new ElementorIntegration( $settings['elementor_plugin_name'] );
            }
            return null;
        } );

        // HubSpot
        IntegrationManager::register( 'hubspot', function() use ( $settings ) {
            if ( empty( $settings['hubspot_api_key'] ) ) {
                error_log( 'ULC: HubSpot API key not configured.' );
                return null;
            }
            if ( class_exists( 'HubSpotIntegration' ) ) {
                return new HubSpotIntegration( $settings['hubspot_api_key'] );
            }
            return null;
        } );

        // Zoho
        IntegrationManager::register( 'zoho', function() use ( $settings ) {
            if ( empty( $settings['zoho_api_url'] ) || empty( $settings['zoho_api_key'] ) ) {
                error_log( 'ULC: Zoho API configuration missing.' );
                return null;
            }
            if ( class_exists( 'ZohoIntegration' ) ) {
                return new ZohoIntegration( $settings['zoho_api_url'], $settings['zoho_api_key'] );
            }
            return null;
        } );

        // Google Ads
        IntegrationManager::register( 'google_ads', function() use ( $settings ) {
            if ( empty( $settings['google_ads_api_key'] ) ) {
                error_log( 'ULC: Google Ads API key not configured.' );
                return null;
            }
            if ( class_exists( 'GoogleAdsIntegration' ) ) {
                return new GoogleAdsIntegration( $settings['google_ads_api_key'] );
            }
            return null;
        } );

        // Facebook Ads
        IntegrationManager::register( 'facebook_ads', function() use ( $settings ) {
            if ( empty( $settings['facebook_app_id'] ) || empty( $settings['facebook_app_secret'] ) || empty( $settings['facebook_access_token'] ) ) {
                error_log( 'ULC: Facebook Ads credentials missing.' );
                return null;
            }
            if ( class_exists( 'FacebookAdsIntegration' ) ) {
                return new FacebookAdsIntegration( $settings['facebook_app_id'], $settings['facebook_app_secret'], $settings['facebook_access_token'] );
            }
            return null;
        } );
    }
}

// Hook into the 'plugins_loaded' action
add_action( 'plugins_loaded', 'ulc_initialize_plugin' );
?>