<?php

namespace Anas\WCCRM\Orders;

use Anas\WCCRM\Contacts\ContactRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Backfill runner for processing existing orders
 */
class BackfillRunner {

    private OrderSyncService $orderSyncService;
    private const OPTION_LAST_ORDER_ID = 'wccrm_backfill_last_order_id';

    public function __construct( ContactRepository $contactRepository ) {
        $this->orderSyncService = new OrderSyncService( $contactRepository );
    }

    /**
     * Run backfill batch processing
     */
    public function run_batch( $limit = 50 ): array {
        $limit = absint( apply_filters( 'wccrm_backfill_batch_size', $limit ) );
        $last_processed_id = get_option( self::OPTION_LAST_ORDER_ID, 0 );
        
        // Get orders to process
        $orders = $this->get_orders_batch( $last_processed_id, $limit );
        
        $results = [
            'processed' => 0,
            'skipped' => 0,
            'errors' => 0,
            'last_id' => $last_processed_id,
            'has_more' => false,
        ];

        if ( empty( $orders ) ) {
            return $results;
        }

        foreach ( $orders as $order_data ) {
            try {
                $order_id = $order_data->ID;
                $order = wc_get_order( $order_id );
                
                if ( ! $order ) {
                    $results['errors']++;
                    continue;
                }

                $contact_id = $this->orderSyncService->link_or_create_contact( $order );
                
                if ( ! $contact_id ) {
                    $results['errors']++;
                    continue;
                }

                // Check if already processed (avoid duplicates)
                if ( $this->orderSyncService->journal_entry_exists( $contact_id, 'order_created', $order_id ) ) {
                    $results['skipped']++;
                } else {
                    // Process order creation
                    $this->orderSyncService->log_journal(
                        $contact_id,
                        'order_created',
                        sprintf( __( 'Order #%s created – status %s (backfill)', 'wccrm' ), $order->get_order_number(), $order->get_status() ),
                        [ 'order_total' => $order->get_total(), 'currency' => $order->get_currency(), 'backfill' => true ],
                        $order_id
                    );

                    // If order is paid, process payment and update totals
                    if ( in_array( $order->get_status(), [ 'processing', 'completed' ], true ) ) {
                        $this->orderSyncService->log_journal(
                            $contact_id,
                            'order_paid',
                            sprintf( __( 'Order #%s paid – status %s (backfill)', 'wccrm' ), $order->get_order_number(), $order->get_status() ),
                            [ 'order_total' => $order->get_total(), 'currency' => $order->get_currency(), 'backfill' => true ],
                            $order_id
                        );

                        // Update contact totals only if not already counted
                        $this->orderSyncService->update_contact_totals( $contact_id, $order->get_total(), false );
                        $this->orderSyncService->maybe_auto_promote_stage( $contact_id, $order );
                    }

                    $results['processed']++;
                }

                $results['last_id'] = $order_id;
                
            } catch ( \Exception $e ) {
                error_log( 'WCCRM Backfill: Error processing order ' . ( $order_id ?? 'unknown' ) . ': ' . $e->getMessage() );
                $results['errors']++;
            }
        }

        // Update last processed ID
        if ( $results['last_id'] > $last_processed_id ) {
            update_option( self::OPTION_LAST_ORDER_ID, $results['last_id'] );
        }

        // Check if there are more orders to process
        $results['has_more'] = count( $orders ) === $limit;

        return $results;
    }

    /**
     * Get batch of orders to process
     */
    private function get_orders_batch( $last_id, $limit ): array {
        global $wpdb;

        $sql = $wpdb->prepare(
            "SELECT ID, post_date FROM {$wpdb->posts} 
             WHERE post_type = 'shop_order' 
             AND ID > %d 
             ORDER BY ID ASC 
             LIMIT %d",
            $last_id,
            $limit
        );

        return $wpdb->get_results( $sql );
    }

    /**
     * Get backfill progress information
     */
    public function get_progress(): array {
        global $wpdb;

        $last_processed_id = get_option( self::OPTION_LAST_ORDER_ID, 0 );
        
        $total_orders = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'shop_order'"
        );

        $remaining_orders = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} 
                 WHERE post_type = 'shop_order' AND ID > %d",
                $last_processed_id
            )
        );

        return [
            'total_orders' => (int) $total_orders,
            'processed_orders' => (int) $total_orders - (int) $remaining_orders,
            'remaining_orders' => (int) $remaining_orders,
            'last_processed_id' => (int) $last_processed_id,
            'progress_percent' => $total_orders > 0 ? round( ( $total_orders - $remaining_orders ) / $total_orders * 100, 1 ) : 100,
        ];
    }

    /**
     * Reset backfill progress
     */
    public function reset_progress(): void {
        delete_option( self::OPTION_LAST_ORDER_ID );
    }
}