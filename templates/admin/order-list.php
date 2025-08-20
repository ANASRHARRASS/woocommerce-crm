<?php
// This file defines the order list template for the admin.

defined( 'ABSPATH' ) || exit;

?>

<div class="wrap">
    <h1><?php esc_html_e( 'Order List', 'woocommerce-crm-plugin' ); ?></h1>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Order ID', 'woocommerce-crm-plugin' ); ?></th>
                <th><?php esc_html_e( 'Customer', 'woocommerce-crm-plugin' ); ?></th>
                <th><?php esc_html_e( 'Total', 'woocommerce-crm-plugin' ); ?></th>
                <th><?php esc_html_e( 'Status', 'woocommerce-crm-plugin' ); ?></th>
                <th><?php esc_html_e( 'Date', 'woocommerce-crm-plugin' ); ?></th>
                <th><?php esc_html_e( 'Actions', 'woocommerce-crm-plugin' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Fetch orders from the database
            $orders = wc_get_orders( array( 'limit' => -1 ) );

            if ( ! empty( $orders ) ) {
                foreach ( $orders as $order ) {
                    echo '<tr>';
                    echo '<td>' . esc_html( $order->get_id() ) . '</td>';
                    echo '<td>' . esc_html( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ) . '</td>';
                    echo '<td>' . esc_html( $order->get_formatted_order_total() ) . '</td>';
                    echo '<td>' . esc_html( wc_get_order_status_name( $order->get_status() ) ) . '</td>';
                    echo '<td>' . esc_html( $order->get_date_created()->date( 'Y-m-d' ) ) . '</td>';
                    echo '<td><a href="' . esc_url( admin_url( 'admin.php?page=order-detail&order_id=' . $order->get_id() ) ) . '">' . esc_html__( 'View', 'woocommerce-crm-plugin' ) . '</a></td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="6">' . esc_html__( 'No orders found.', 'woocommerce-crm-plugin' ) . '</td></tr>';
            }
            ?>
        </tbody>
    </table>
</div>