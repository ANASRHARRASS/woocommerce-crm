<?php

namespace Anas\WCCRM\Orders;

use Anas\WCCRM\Contacts\ContactRepository;

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce hooks integration for order synchronization
 */
class OrderHooks {

    private OrderSyncService $orderSyncService;

    public function __construct( ContactRepository $contactRepository ) {
        $this->orderSyncService = new OrderSyncService( $contactRepository );
        $this->init_hooks();
    }

    /**
     * Initialize WooCommerce hooks
     */
    private function init_hooks(): void {
        // Order creation
        add_action( 'woocommerce_checkout_order_processed', [ $this, 'on_order_created' ], 10, 1 );
        
        // Order status changes
        add_action( 'woocommerce_order_status_changed', [ $this, 'on_status_changed' ], 10, 3 );
        
        // Order refunds
        add_action( 'woocommerce_order_refunded', [ $this, 'on_order_refunded' ], 10, 2 );
    }

    /**
     * Handle order creation
     */
    public function on_order_created( $order_id ): void {
        try {
            $this->orderSyncService->process_order_created( $order_id );
        } catch ( \Exception $e ) {
            error_log( 'WCCRM OrderHooks: Error processing order creation ' . $order_id . ': ' . $e->getMessage() );
        }
    }

    /**
     * Handle order status changes
     */
    public function on_status_changed( $order_id, $old_status, $new_status ): void {
        try {
            $this->orderSyncService->process_status_change( $order_id, $old_status, $new_status );
        } catch ( \Exception $e ) {
            error_log( 'WCCRM OrderHooks: Error processing status change for order ' . $order_id . ': ' . $e->getMessage() );
        }
    }

    /**
     * Handle order refunds
     */
    public function on_order_refunded( $order_id, $refund_id ): void {
        try {
            // Get refund details
            $refund = wc_get_order( $refund_id );
            if ( $refund ) {
                $args = [
                    'order_id' => $order_id,
                    'amount' => abs( $refund->get_amount() ),
                ];
                $this->orderSyncService->process_refund( $refund_id, $args );
            }
        } catch ( \Exception $e ) {
            error_log( 'WCCRM OrderHooks: Error processing refund for order ' . $order_id . ': ' . $e->getMessage() );
        }
    }

    /**
     * Get the order sync service instance
     */
    public function get_order_sync_service(): OrderSyncService {
        return $this->orderSyncService;
    }
}