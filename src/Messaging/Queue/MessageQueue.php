<?php

namespace Anas\WCCRM\Messaging\Queue;

defined('ABSPATH') || exit;

class MessageQueue
{
    private string $table;
    public function __construct()
    {
        global $wpdb;
        $this->table = $wpdb->prefix . 'wccrm_message_queue';
    }
    public function enqueue(int $contact_id = null, string $channel = 'email', string $template_key = null, array $payload = [], \DateTimeInterface $when = null): ?int
    {
        global $wpdb;
        $wpdb->insert($this->table, ['contact_id' => $contact_id, 'channel' => $channel, 'template_key' => $template_key, 'payload_json' => wp_json_encode($payload), 'status' => 'pending', 'attempts' => 0, 'scheduled_at' => $when ? $when->format('Y-m-d H:i:s') : null, 'created_at' => current_time('mysql')], ['%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s']);
        $id = $wpdb->insert_id ?: null;
        if ($id) {
            do_action('wccrm_queue_changed');
        }
        return $id;
    }
    public function due(int $limit = 20): array
    {
        global $wpdb;
        $now = current_time('mysql');
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->table} WHERE status='pending' AND (scheduled_at IS NULL OR scheduled_at <= %s) ORDER BY id ASC LIMIT %d", $now, $limit), ARRAY_A) ?: [];
    }
    public function mark_sent(int $id): void
    {
        global $wpdb;
        $wpdb->update($this->table, ['status' => 'sent', 'updated_at' => current_time('mysql')], ['id' => $id], ['%s', '%s'], ['%d']);
        do_action('wccrm_queue_changed');
    }
    public function mark_failed(int $id, string $error): void
    {
        global $wpdb;
        $wpdb->query($wpdb->prepare("UPDATE {$this->table} SET attempts=attempts+1,last_error=%s,updated_at=%s WHERE id=%d", $error, current_time('mysql'), $id));
        do_action('wccrm_queue_changed');
    }
    public function count_pending(): int
    {
        global $wpdb;
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table} WHERE status='pending'");
    }
}
