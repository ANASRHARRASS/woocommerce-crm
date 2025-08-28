<?php
/**
 * Uninstall script for WooCommerce CRM
 * Only removes data if KSCRM_REMOVE_ALL_DATA constant is true
 */

// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Only remove data if explicitly instructed
if ( ! defined( 'KSCRM_REMOVE_ALL_DATA' ) || ! KSCRM_REMOVE_ALL_DATA ) {
    // Do not remove any data by default
    return;
}

global $wpdb;

// Remove custom tables
$tables_to_drop = [
    $wpdb->prefix . 'kscrm_leads',
    $wpdb->prefix . 'kscrm_utm_stats',
    $wpdb->prefix . 'kscrm_contacts',
    $wpdb->prefix . 'kscrm_contact_interests',
    $wpdb->prefix . 'kscrm_forms',
    $wpdb->prefix . 'kscrm_form_submissions',
    $wpdb->prefix . 'kscrm_shipping_quotes',
    // Legacy tables
    $wpdb->prefix . 'crm_leads',
    $wpdb->prefix . 'wcp_leads',
];

foreach ( $tables_to_drop as $table ) {
    $wpdb->query( "DROP TABLE IF EXISTS {$table}" );
}

// Remove plugin options
$options_to_delete = [
    'wccrm_version',
    'wccrm_db_version',
    'wcp_api_key',
    'kscrm_leads_retention_months',
    'kscrm_utm_retention_days',
    'kscrm_last_retention_cleanup',
    // Legacy options
    'woocommerce_crm_plugin_options',
    'wcp_version',
    'wcp_tokens',
];

foreach ( $options_to_delete as $option ) {
    delete_option( $option );
}

// Remove user meta related to the plugin
$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'kscrm_%'" );
$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'wccrm_%'" );

// Remove transients and cache
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_kscrm_%'" );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_kscrm_%'" );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wccrm_%'" );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_wccrm_%'" );

// Clear scheduled hooks
wp_clear_scheduled_hook( 'kscrm_daily_retention_cleanup' );
wp_clear_scheduled_hook( 'wccrm_cleanup_old_interests' );

// Remove upload directory exports (if exists)
$upload_dir = wp_upload_dir();
$export_dir = $upload_dir['basedir'] . '/kscrm-exports';

if ( is_dir( $export_dir ) ) {
    // Remove all files in the directory
    $files = glob( $export_dir . '/*' );
    foreach ( $files as $file ) {
        if ( is_file( $file ) ) {
            unlink( $file );
        }
    }
    
    // Remove the directory itself
    rmdir( $export_dir );
}

// Log the uninstall for debugging
error_log( 'WooCommerce CRM: Complete uninstall performed (KSCRM_REMOVE_ALL_DATA was true)' );