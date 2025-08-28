<?php

namespace Anas\WCCRM\Messaging\Templates;

defined('ABSPATH') || exit;

class TemplateRepository
{
    private string $table;
    public function __construct()
    {
        global $wpdb;
        $this->table = $wpdb->prefix . 'wccrm_message_templates';
    }

    public function get(string $key): ?array
    {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table} WHERE template_key=%s", $key), ARRAY_A);
        return $row ?: null;
    }
    public function all(int $limit = 100, int $offset = 0): array
    {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$this->table} ORDER BY id DESC LIMIT %d OFFSET %d", $limit, $offset), ARRAY_A) ?: [];
    }
    public function save(string $key, string $channel, string $subject = null, string $body = ''): bool
    {
        global $wpdb;
        $existing = $this->get($key);
        if ($existing) {
            $wpdb->update($this->table, ['channel' => $channel, 'subject' => $subject, 'body' => $body, 'updated_at' => current_time('mysql')], ['template_key' => $key], ['%s', '%s', '%s', '%s'], ['%s']);
            return true;
        } else {
            return false !== $wpdb->insert($this->table, ['template_key' => $key, 'channel' => $channel, 'subject' => $subject, 'body' => $body, 'created_at' => current_time('mysql'), 'updated_at' => current_time('mysql')], ['%s', '%s', '%s', '%s', '%s', '%s']);
        }
    }
    public function delete(string $key): bool
    {
        global $wpdb;
        return (bool) $wpdb->delete($this->table, ['template_key' => $key], ['%s']);
    }
    public function count(): int
    {
        global $wpdb;
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table}");
    }
}
