<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Function to run on plugin uninstall
function woocommerce_crm_plugin_uninstall() {
    // Remove options from the database
    delete_option( 'woocommerce_crm_plugin_options' );

    // Remove custom database tables if any
    global $wpdb;
    $table_name = $wpdb->prefix . 'crm_leads';
    $wpdb->query( "DROP TABLE IF EXISTS $table_name" );

    // Additional cleanup can be added here
}

// Hook the uninstall function
register_uninstall_hook( __FILE__, 'woocommerce_crm_plugin_uninstall' );
?>