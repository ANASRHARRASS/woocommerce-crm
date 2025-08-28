<?php

namespace Anas\WCCRM\Orders;

use Anas\WCCRM\Contacts\ContactRepository;
use WC_Order;

defined('ABSPATH') || exit;

/**
 * Syncs WooCommerce order events into internal CRM domain (stub implementation).
 */
class OrderSyncService
{
    private OrderMetricsUpdater $metrics;
    private ContactRepository $contacts;

    public function __construct(OrderMetricsUpdater $metrics, ContactRepository $contacts)
    {
        $this->metrics  = $metrics;
        $this->contacts = $contacts;
    }

    /**
     * Fired on 'woocommerce_new_order'.
     */
    public function handle_new_order(int $order_id): void
    {
        $order = wc_get_order($order_id);
        if (! $order instanceof WC_Order) {
            return;
        }
        $this->link_contact($order);
        $this->metrics->update_for_order($order_id);
        do_action('wccrm_order_event', 'new_order', $order_id);
    }

    /**
     * Fired on 'woocommerce_order_status_changed'.
     */
    public function handle_status_change(int $order_id, string $old_status, string $new_status, $order): void
    {
        if ($order instanceof WC_Order) {
            $this->metrics->update_for_order($order_id);
        }
        do_action('wccrm_order_event', 'status_change', [
            'order_id'   => $order_id,
            'old_status' => $old_status,
            'new_status' => $new_status,
        ]);
    }

    /**
     * Associate order with a contact record (very lightweight placeholder).
     */
    private function link_contact(WC_Order $order): void
    {
        $email = $order->get_billing_email();
        if (! $email) {
            return;
        }
        $this->contacts->upsert_by_email_or_phone([
            'email'      => $email,
            'first_name' => $order->get_billing_first_name(),
            'last_name'  => $order->get_billing_last_name(),
        ]);
    }
}
