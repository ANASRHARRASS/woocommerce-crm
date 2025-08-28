<?php

namespace Anas\WCCRM\Orders;

use Anas\WCCRM\Contacts\ContactRepository;

defined('ABSPATH') || exit;

/**
 * Collects / updates aggregate metrics for orders (stub implementation).
 * Real logic can later compute LTV, AOV, order counts, etc.
 */
class OrderMetricsUpdater
{
    private ContactRepository $contacts;

    public function __construct(ContactRepository $contacts)
    {
        $this->contacts = $contacts;
    }

    /**
     * Update metrics for a single order (placeholder).
     */
    public function update_for_order(int $order_id): void
    {
        // TODO: implement aggregation logic (order counts, totals, last order date, etc.)
        do_action('wccrm_debug_log', 'OrderMetricsUpdater:update_for_order', ['order_id' => $order_id]);
    }
}
