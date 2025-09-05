<?php

namespace Anas\WCCRM\Forms;

defined('ABSPATH') || exit;

/**
 * Form repository for CRUD operations
 */
class FormRepository
{

    private string $table_name;

    public function __construct()
    {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'wccrm_forms';
    }

    public function create(array $data): ?FormModel
    {
        global $wpdb;

        $insert_data = [
            'form_key' => sanitize_key($data['form_key'] ?? ''),
            'name' => sanitize_text_field($data['name'] ?? ''),
            'schema_json' => wp_json_encode($data['schema'] ?? []),
            'status' => sanitize_text_field($data['status'] ?? 'active'),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ];

        $result = $wpdb->insert(
            $this->table_name,
            $insert_data,
            ['%s', '%s', '%s', '%s', '%s', '%s']
        );

        if ($result === false) {
            return null;
        }

        $insert_data['id'] = $wpdb->insert_id;
        $model = new FormModel($insert_data);
        // Sync normalized fields
        if (class_exists('Anas\\WCCRM\\Forms\\FieldRepository')) {
            (new FieldRepository())->sync_from_schema($model);
        }
        return $model;
    }

    public function update(int $id, array $data): ?FormModel
    {
        global $wpdb;

        $update_data = [
            'updated_at' => current_time('mysql'),
        ];

        if (isset($data['name'])) {
            $update_data['name'] = sanitize_text_field($data['name']);
        }

        if (isset($data['schema'])) {
            $update_data['schema_json'] = wp_json_encode($data['schema']);
        }

        if (isset($data['status'])) {
            $update_data['status'] = sanitize_text_field($data['status']);
        }

        $result = $wpdb->update(
            $this->table_name,
            $update_data,
            ['id' => $id],
            array_fill(0, count($update_data), '%s'),
            ['%d']
        );

        if ($result === false) {
            return null;
        }

        $model = $this->load_by_id($id);
        if ($model && isset($data['schema']) && class_exists('Anas\\WCCRM\\Forms\\FieldRepository')) {
            (new FieldRepository())->sync_from_schema($model);
        }
        return $model;
    }

    public function delete(int $id): bool
    {
        global $wpdb;

        $result = $wpdb->delete(
            $this->table_name,
            ['id' => $id],
            ['%d']
        );

        return $result !== false;
    }

    public function load_by_id(int $id): ?FormModel
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE id = %d",
                $id
            ),
            ARRAY_A
        );

        return $row ? new FormModel($row) : null;
    }

    public function load_by_key(string $form_key): ?FormModel
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE form_key = %s",
                $form_key
            ),
            ARRAY_A
        );

        return $row ? new FormModel($row) : null;
    }

    public function list_active(): array
    {
        global $wpdb;

        $rows = $wpdb->get_results(
            "SELECT * FROM {$this->table_name} WHERE status = 'active' ORDER BY name ASC",
            ARRAY_A
        );

        return array_map(function ($row) {
            return new FormModel($row);
        }, $rows);
    }

    public function list_all(int $page = 1, int $per_page = 20): array
    {
        global $wpdb;

        $offset = max(0, ($page - 1) * $per_page);

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table_name} ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            ),
            ARRAY_A
        );

        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");

        return [
            'items' => array_map(function ($row) {
                return new FormModel($row);
            }, $rows),
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page,
        ];
    }

    /**
     * Duplicate a form by ID.
     * - Clones name (adds Copy suffix) and schema
     * - Generates a new unique form_key based on original form_key + '-copy'
     */
    public function duplicate(int $id): ?FormModel
    {
        $orig = $this->load_by_id($id);
        if (!$orig) return null;
        $base_key = $orig->form_key . '-copy';
        $new_key = $base_key;
        $i = 2;
        while ($this->load_by_key($new_key) && $i < 50) {
            $new_key = $base_key . $i;
            $i++;
        }
        $schema = [
            'fields' => $orig->get_fields(),
            'settings' => $orig->get_settings(),
        ];
        return $this->create([
            'form_key' => $new_key,
            'name' => $orig->name . ' Copy',
            'schema' => $schema,
            'status' => $orig->status,
        ]);
    }
}
