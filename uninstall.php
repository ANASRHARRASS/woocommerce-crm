<?php
// Proper direct uninstall handler.
// WordPress includes this file automatically on uninstall if present.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Remove plugin options.
delete_option( 'woocommerce_crm_plugin_options' );
delete_option( 'wcp_version' );
delete_option( 'wcp_tokens' ); // integration tokens array.

// Drop custom tables (if they exist).
global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}crm_leads" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wcp_leads" );

// Add any additional cleanup (transients, user meta, etc.) here.
?>