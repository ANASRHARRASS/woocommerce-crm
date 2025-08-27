<?php

namespace Anas\WCCRM\Orders;

use Anas\WCCRM\Contacts\ContactRepository;
use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * Order synchronization service for WooCommerce
 */
class OrderSyncService {

    private ContactRepository $contactRepository;

    public function __construct( ContactRepository $contactRepository ) {
        $this->contactRepository = $contactRepository;
    }

    /**
     * Link order to contact or create new contact if needed
     */
    public function link_or_create_contact( WC_Order $order ): int {
        $billing_email = strtolower( trim( $order->get_billing_email() ) );
        $billing_phone = preg_replace( '/[^0-9]/', '', $order->get_billing_phone() );
        
        $contact_data = [
            'email' => $billing_email ?: null,
            'phone' => $billing_phone ?: null,
            'first_name' => $order->get_billing_first_name(),
            'last_name' => $order->get_billing_last_name(),
        ];

        $contact_id = $this->contactRepository->upsert_by_email_or_phone( $contact_data );
        
        if ( ! $contact_id ) {
            // Fallback: create contact with minimal data
            $contact_id = $this->contactRepository->create( $contact_data );
        }

        return $contact_id ?: 0;
    }

    /**
     * Process order creation event
     */
    public function process_order_created( $order_id ): void {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        $contact_id = $this->link_or_create_contact( $order );
        if ( ! $contact_id ) {
            error_log( 'WCCRM: Failed to link order ' . $order_id . ' to contact' );
            return;
        }

        $this->log_journal( 
            $contact_id, 
            'order_created', 
            sprintf( __( 'Order #%s created – status %s', 'wccrm' ), $order->get_order_number(), $order->get_status() ),
            [ 'order_total' => $order->get_total(), 'currency' => $order->get_currency() ],
            $order_id
        );

        do_action( 'wccrm_order_linked_to_contact', $order_id, $contact_id );
    }

    /**
     * Process order status change
     */
    public function process_status_change( $order_id, $old_status, $new_status ): void {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        $contact_id = $this->link_or_create_contact( $order );
        if ( ! $contact_id ) {
            return;
        }

        // Check if this is a transition to paid status
        if ( in_array( $new_status, [ 'processing', 'completed' ], true ) && 
             ! in_array( $old_status, [ 'processing', 'completed' ], true ) ) {
            
            $this->log_journal(
                $contact_id,
                'order_paid',
                sprintf( __( 'Order #%s paid – status %s', 'wccrm' ), $order->get_order_number(), $new_status ),
                [ 'order_total' => $order->get_total(), 'currency' => $order->get_currency() ],
                $order_id
            );

            // Update contact totals for paid orders
            $this->update_contact_totals( $contact_id, $order->get_total(), false );
            
            // Update last order ID
            $this->update_last_order_id( $contact_id, $order_id );

            // Maybe auto-promote stage
            $this->maybe_auto_promote_stage( $contact_id, $order );
        }
    }

    /**
     * Process refund event
     */
    public function process_refund( $refund_id, $args ): void {
        if ( empty( $args['order_id'] ) ) {
            return;
        }

        $order = wc_get_order( $args['order_id'] );
        if ( ! $order ) {
            return;
        }

        $contact_id = $this->link_or_create_contact( $order );
        if ( ! $contact_id ) {
            return;
        }

        $refund_amount = floatval( $args['amount'] ?? 0 );
        
        $this->log_journal(
            $contact_id,
            'order_refunded',
            sprintf( __( 'Order #%s refunded amount %s', 'wccrm' ), $order->get_order_number(), $refund_amount ),
            [ 'refund_amount' => $refund_amount, 'currency' => $order->get_currency() ],
            $refund_id
        );

        // Update contact totals (subtract refund)
        $this->update_contact_totals( $contact_id, $refund_amount, true );
    }

    /**
     * Update contact order totals
     */
    public function update_contact_totals( $contact_id, $order_amount, $is_refund = false ): void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wccrm_contacts';
        
        if ( $is_refund ) {
            // Subtract refund amount but don't go below 0
            $sql = $wpdb->prepare(
                "UPDATE {$table_name} 
                 SET total_spent = GREATEST(0, total_spent - %f), 
                     updated_at = %s 
                 WHERE id = %d",
                $order_amount,
                current_time( 'mysql' ),
                $contact_id
            );
        } else {
            // Add order amount and increment order count
            $sql = $wpdb->prepare(
                "UPDATE {$table_name} 
                 SET total_spent = total_spent + %f, 
                     order_count = order_count + 1,
                     updated_at = %s 
                 WHERE id = %d",
                $order_amount,
                current_time( 'mysql' ),
                $contact_id
            );
        }

        $wpdb->query( $sql );

        // Get updated totals for action
        $contact = $wpdb->get_row(
            $wpdb->prepare( "SELECT total_spent, order_count FROM {$table_name} WHERE id = %d", $contact_id ),
            ARRAY_A
        );

        if ( $contact ) {
            do_action( 'wccrm_contact_totals_updated', $contact_id, $contact );
        }
    }

    /**
     * Update last order ID for contact
     */
    private function update_last_order_id( $contact_id, $order_id ): void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wccrm_contacts';
        
        $wpdb->update(
            $table_name,
            [ 'last_order_id' => $order_id, 'updated_at' => current_time( 'mysql' ) ],
            [ 'id' => $contact_id ],
            [ '%d', '%s' ],
            [ '%d' ]
        );
    }

    /**
     * Maybe auto-promote contact stage
     */
    public function maybe_auto_promote_stage( $contact_id, WC_Order $order ): void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wccrm_contacts';
        
        $contact = $wpdb->get_row(
            $wpdb->prepare( "SELECT stage FROM {$table_name} WHERE id = %d", $contact_id ),
            ARRAY_A
        );

        if ( ! $contact || $contact['stage'] === 'customer' ) {
            return;
        }

        // Auto-promote to customer on first paid order
        $wpdb->update(
            $table_name,
            [ 'stage' => 'customer', 'updated_at' => current_time( 'mysql' ) ],
            [ 'id' => $contact_id ],
            [ '%s', '%s' ],
            [ '%d' ]
        );

        $this->log_journal(
            $contact_id,
            'stage_auto_promote',
            __( 'Auto-promoted to customer stage after first paid order', 'wccrm' ),
            [ 'old_stage' => $contact['stage'], 'new_stage' => 'customer', 'trigger_order_id' => $order->get_id() ],
            $order->get_id()
        );
    }

    /**
     * Log journal entry for contact
     */
    public function log_journal( $contact_id, $event_type, $message, $meta = [], $ref_id = null ): void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wccrm_lead_journal';
        
        $wpdb->insert(
            $table_name,
            [
                'contact_id' => $contact_id,
                'event_type' => $event_type,
                'message' => $message,
                'meta_data' => $meta ? wp_json_encode( $meta ) : null,
                'ref_id' => $ref_id,
                'created_at' => current_time( 'mysql' ),
            ],
            [ '%d', '%s', '%s', '%s', '%d', '%s' ]
        );
    }

    /**
     * Check if journal entry already exists for order
     */
    public function journal_entry_exists( $contact_id, $event_type, $ref_id ): bool {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wccrm_lead_journal';
        
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT 1 FROM {$table_name} 
                 WHERE contact_id = %d AND event_type = %s AND ref_id = %d 
                 LIMIT 1",
                $contact_id,
                $event_type,
                $ref_id
            )
        );

        return (bool) $exists;
    }
}