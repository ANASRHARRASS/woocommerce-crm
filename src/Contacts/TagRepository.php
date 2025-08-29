<?php

namespace Anas\WCCRM\Contacts;

defined('ABSPATH') || exit;

/**
 * Repository for contact tags and mapping table.
 */
class TagRepository
{
    private string $tags_table;
    private string $map_table;

    public function __construct()
    {
        global $wpdb;
        $this->tags_table = $wpdb->prefix . 'wccrm_contact_tags';
        $this->map_table  = $wpdb->prefix . 'wccrm_contact_tag_map';
    }

    public function ensure(string $tag_key, ?string $name = null): ?int
    {
        $tag_key = sanitize_key($tag_key);
        if ($tag_key === '') return null;
        $existing = $this->get_by_key($tag_key);
        if ($existing) return (int)$existing['id'];
        global $wpdb;
        $wpdb->insert($this->tags_table, [
            'tag_key' => $tag_key,
            'name'    => $name ? sanitize_text_field($name) : $tag_key,
            'created_at' => current_time('mysql')
        ], ['%s','%s','%s']);
        return (int)$wpdb->insert_id;
    }

    public function get_by_key(string $tag_key): ?array
    {
        global $wpdb; $tag_key = sanitize_key($tag_key);
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->tags_table} WHERE tag_key = %s", $tag_key), ARRAY_A);
        return $row ?: null;
    }

    public function assign(int $contact_id, array $tag_keys): void
    {
        if ($contact_id <= 0 || empty($tag_keys)) return;
        global $wpdb;
        foreach ($tag_keys as $tag_key) {
            $tid = $this->ensure($tag_key);
            if (!$tid) continue;
            // Insert ignore semantics
            $wpdb->query($wpdb->prepare(
                "INSERT IGNORE INTO {$this->map_table} (contact_id, tag_id, added_at) VALUES (%d,%d,%s)",
                $contact_id, $tid, current_time('mysql')
            ));
        }
        do_action('wccrm_contact_tags_assigned', $contact_id, $tag_keys);
    }

    public function list_for_contact(int $contact_id): array
    {
        global $wpdb;
        return $wpdb->get_col($wpdb->prepare(
            "SELECT t.tag_key FROM {$this->map_table} m JOIN {$this->tags_table} t ON m.tag_id = t.id WHERE m.contact_id = %d",
            $contact_id
        )) ?: [];
    }
}
