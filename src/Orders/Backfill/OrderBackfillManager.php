<?php

namespace Anas\WCCRM\Orders\Backfill;

use Anas\WCCRM\Orders\OrderSyncService;

defined('ABSPATH') || exit;

/**
 * Processes historic orders in batches for initial synchronization (stub implementation).
 */
class OrderBackfillManager
{
    private OrderSyncService $sync;
    private int $batch_size;

    public function __construct(OrderSyncService $sync, $contactRepository)
    { // second arg kept for forward compat
        $this->sync       = $sync;
        $this->batch_size = apply_filters('wccrm_backfill_batch_size', 25);
    }

    /**
     * Cron hook target. Processes the next batch of unsynced orders.
     */
    public function process_next_batch(): void
    {
        if (! function_exists('wc_get_orders')) {
            return;
        }
        $orders = wc_get_orders([
            'limit'        => $this->batch_size,
            'orderby'      => 'date',
            'order'        => 'ASC',
            'return'       => 'ids',
            'type'         => 'shop_order',
            'status'       => array_keys(wc_get_order_statuses()),
            // Placeholder meta query / flag for unsynced could be added later
        ]);
        foreach ($orders as $order_id) {
            $this->sync->handle_new_order((int) $order_id);
        }
    }
}
