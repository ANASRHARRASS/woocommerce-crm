<?php

namespace Anas\WCCRM\Reporting;

defined('ABSPATH') || exit;

class MetricsAggregator
{
    public function collect(): array
    {
        // Simple transient cache (60s) to reduce DB load.
        $cache_key = 'wccrm_metrics_v1';
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            return $cached;
        }
        global $wpdb;

        // Contacts count (custom table)
        $contacts = 0;
        $table = $wpdb->prefix . 'wccrm_contacts';
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table))) {
            $contacts = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        }

        // Orders count (WooCommerce)
        $orders = function_exists('wc_orders_count') ? array_sum(wc_orders_count(null)) : (int) wp_count_posts('shop_order')->publish ?? 0;

        // Average order value (simple gross avg of completed orders)
        $avg_value = 0.0;
        if (function_exists('wc_get_orders')) {
            $recent = wc_get_orders([
                'limit' => 25,
                'status' => ['wc-completed','completed'],
                'orderby' => 'date',
                'order' => 'DESC',
                'return' => 'ids'
            ]);
            $sum = 0; $n=0;
            foreach ($recent as $oid) { $order = wc_get_order($oid); if ($order) { $sum += (float) $order->get_total(); $n++; } }
            if ($n>0) { $avg_value = $sum / $n; }
        }

        // Messages queued (if table exists)
        $messages_queued = 0;
        $queue_table = $wpdb->prefix . 'wccrm_message_queue';
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $queue_table))) {
            $messages_queued = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$queue_table} WHERE status='pending'");
        }

        $data = [
            'contacts' => $contacts,
            'orders' => $orders,
            'avg_order_value' => round($avg_value,2),
            'messages_queued' => $messages_queued,
        ];
        set_transient($cache_key, $data, 60); // cache for 1 minute
        return $data;
    }
    /** Invalidate metrics cache when something changes (templates, queue, contacts). */
    public static function invalidate_cache(): void
    {
        delete_transient('wccrm_metrics_v1');
    }
