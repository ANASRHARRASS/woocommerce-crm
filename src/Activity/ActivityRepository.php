<?php

namespace Anas\WCCRM\Activity;

defined('ABSPATH') || exit;

class ActivityRepository
{
    private string $table;
    public function __construct()
    {
        global $wpdb;
        $this->table = $wpdb->prefix . 'wccrm_contact_activity';
    }
    public function log(int $contact_id, string $type, array $meta = []): bool
    {
        global $wpdb;
        return false !== $wpdb->insert($this->table, ['contact_id' => $contact_id, 'type' => $type, 'ref_id' => $meta['ref_id'] ?? null, 'meta_json' => wp_json_encode($meta), 'created_at' => current_time('mysql')], ['%d', '%s', '%d', '%s', '%s']);
    }
    public function recent_for_contact(int $contact_id, int $limit = 25): array
    {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->table} WHERE contact_id=%d ORDER BY id DESC LIMIT %d", $contact_id, $limit), ARRAY_A) ?: [];
    }
}
