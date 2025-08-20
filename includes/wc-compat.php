<?php
// This file ensures compatibility with WooCommerce.

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class WC_Compat {
    public function __construct() {
        add_action( 'woocommerce_init', array( $this, 'init' ) );
    }

    public function init() {
        // Check if WooCommerce is active
        if ( ! class_exists( 'WooCommerce' ) ) {
            add_action( 'admin_notices', array( $this, 'woocommerce_not_active_notice' ) );
            return;
        }

        // Add compatibility functions or hooks here
    }

    public function woocommerce_not_active_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php _e( 'WooCommerce CRM Plugin requires WooCommerce to be installed and active.', 'woocommerce-crm-plugin' ); ?></p>
        </div>
        <?php
    }
}

new WC_Compat();
?>