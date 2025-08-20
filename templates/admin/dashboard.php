<?php
// This file defines the admin dashboard template for the WooCommerce CRM Plugin.

defined('ABSPATH') || exit;

?>

<div class="wrap">
    <h1><?php esc_html_e('WooCommerce CRM Dashboard', 'woocommerce-crm-plugin'); ?></h1>

    <div class="dashboard-widgets">
        <div class="widget">
            <h2><?php esc_html_e('Overview', 'woocommerce-crm-plugin'); ?></h2>
            <p><?php esc_html_e('Manage your contacts, orders, and integrations from this dashboard.', 'woocommerce-crm-plugin'); ?></p>
        </div>

        <div class="widget">
            <h2><?php esc_html_e('Recent Orders', 'woocommerce-crm-plugin'); ?></h2>
            <ul>
                <?php
                // Fetch and display recent orders
                $recent_orders = wc_get_orders(array('limit' => 5));
                foreach ($recent_orders as $order) {
                    echo '<li>' . esc_html($order->get_order_number()) . ' - ' . esc_html($order->get_total()) . '</li>';
                }
                ?>
            </ul>
        </div>

        <div class="widget">
            <h2><?php esc_html_e('Leads from Social Media', 'woocommerce-crm-plugin'); ?></h2>
            <p><?php esc_html_e('Capture leads from platforms like TikTok, Facebook, and Instagram.', 'woocommerce-crm-plugin'); ?></p>
        </div>

        <div class="widget">
            <h2><?php esc_html_e('Shipping Management', 'woocommerce-crm-plugin'); ?></h2>
            <p><?php esc_html_e('Manage your shipping methods and rates efficiently.', 'woocommerce-crm-plugin'); ?></p>
        </div>
    </div>
</div>