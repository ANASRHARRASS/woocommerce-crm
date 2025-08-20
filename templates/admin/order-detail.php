<?php
// This file defines the order detail template for the admin.

defined('ABSPATH') || exit;

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if (!$order_id) {
    echo '<div class="error"><p>' . __('Invalid order ID.', 'woocommerce-crm-plugin') . '</p></div>';
    return;
}

$order = wc_get_order($order_id);

if (!$order) {
    echo '<div class="error"><p>' . __('Order not found.', 'woocommerce-crm-plugin') . '</p></div>';
    return;
}

// Display order details
?>
<div class="wrap">
    <h1><?php echo __('Order Details', 'woocommerce-crm-plugin'); ?> #<?php echo $order->get_order_number(); ?></h1>
    
    <h2><?php echo __('Customer Information', 'woocommerce-crm-plugin'); ?></h2>
    <p><?php echo __('Name:', 'woocommerce-crm-plugin') . ' ' . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(); ?></p>
    <p><?php echo __('Email:', 'woocommerce-crm-plugin') . ' ' . $order->get_billing_email(); ?></p>
    
    <h2><?php echo __('Order Items', 'woocommerce-crm-plugin'); ?></h2>
    <ul>
        <?php foreach ($order->get_items() as $item_id => $item): ?>
            <li><?php echo $item->get_name() . ' x ' . $item->get_quantity(); ?></li>
        <?php endforeach; ?>
    </ul>
    
    <h2><?php echo __('Shipping Information', 'woocommerce-crm-plugin'); ?></h2>
    <p><?php echo __('Shipping Method:', 'woocommerce-crm-plugin') . ' ' . $order->get_shipping_method(); ?></p>
    <p><?php echo __('Shipping Address:', 'woocommerce-crm-plugin') . ' ' . $order->get_shipping_address_1() . ', ' . $order->get_shipping_city() . ', ' . $order->get_shipping_postcode(); ?></p>
    
    <h2><?php echo __('Order Status', 'woocommerce-crm-plugin'); ?></h2>
    <p><?php echo wc_get_order_status_name($order->get_status()); ?></p>
    
    <h2><?php echo __('Total Amount', 'woocommerce-crm-plugin'); ?></h2>
    <p><?php echo $order->get_formatted_order_total(); ?></p>
</div>