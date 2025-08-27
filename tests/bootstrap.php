<?php
// This file sets up the testing environment for the WooCommerce CRM Plugin.

require_once dirname(__DIR__) . '/woocommerce-crm.php';

// Load the plugin's main file.
if ( ! defined( 'WP_TESTS_DIR' ) ) {
    exit( 'WP_TESTS_DIR is not defined.' );
}

require_once WP_TESTS_DIR . '/includes/functions.php';

function _manually_load_plugin() {
    require dirname( __DIR__ ) . '/woocommerce-crm.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require WP_TESTS_DIR . '/includes/bootstrap.php';