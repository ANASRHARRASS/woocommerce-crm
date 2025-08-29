<?php

namespace Anas\WCCRM\Forms;

defined('ABSPATH') || exit;

/**
 * Normalized form field repository.
 * Stores parsed fields from schema_json into dedicated tables for querying / variant overrides.
 */
class FieldRepository
{
    private string $fields_table;
    private string $options_table;

    public function __construct()
    {
        global $wpdb;
        $this->fields_table  = $wpdb->prefix . 'wccrm_form_fields';
        $this->options_table = $wpdb->prefix . 'wccrm_form_field_options';
    }

    /**
     * Synchronize normalized tables from a form schema (idempotent: we recreate rows each time for simplicity now).
     */
    public function sync_from_schema(FormModel $form): void
    {
        $fields = $form->get_fields();
        if (empty($fields)) {
            return; // nothing to sync
        }
        global $wpdb;
        $form_id = $form->id;
        // Remove existing rows (simple approach; later optimize with diffing if needed)
        $wpdb->delete($this->fields_table, ['form_id' => $form_id], ['%d']);
        // Collect select option inserts after we know field IDs
        foreach ($fields as $position => $field) {
            $field_key = sanitize_key($field['name'] ?? $field['field_key'] ?? ('f_' . $position));
            if (!$field_key) continue;
            $type      = sanitize_key($field['type'] ?? 'text');
            $label     = sanitize_text_field($field['label'] ?? $field_key);
            $required  = !empty($field['required']) ? 1 : 0;
            $config    = $this->extract_config($field);
            $wpdb->insert(
                $this->fields_table,
                [
                    'form_id'     => $form_id,
                    'field_key'   => $field_key,
                    'type'        => $type,
                    'label'       => $label,
                    'position'    => (int)$position,
                    'config_json' => wp_json_encode($config),
                    'required'    => $required,
                    'created_at'  => current_time('mysql'),
                    'updated_at'  => current_time('mysql'),
                ],
                ['%d','%s','%s','%s','%d','%s','%d','%s','%s']
            );
            $field_id = (int)$wpdb->insert_id;
            if ($field_id && $type === 'select') {
                $options = $field['options'] ?? [];
                foreach ($options as $sort => $opt) {
                    $val = sanitize_text_field($opt['value'] ?? '');
                    if ($val === '') continue;
                    $wpdb->insert(
                        $this->options_table,
                        [
                            'field_id'  => $field_id,
                            'opt_value' => $val,
                            'opt_label' => sanitize_text_field($opt['label'] ?? $val),
                            'sort'      => (int)$sort,
                        ],
                        ['%d','%s','%s','%d']
                    );
                }
            }
        }
        // Action for extensions
        do_action('wccrm_form_fields_synced', $form_id);
    }

    /**
     * Get normalized fields (with merged config + options) for a form ID.
     */
    public function get_fields_for_form(int $form_id): array
    {
        global $wpdb;
        $rows = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$this->fields_table} WHERE form_id = %d ORDER BY position ASC", $form_id),
            ARRAY_A
        ) ?: [];
        if (!$rows) return [];
        // Load options for select fields in one query
        $field_ids = array_column($rows, 'id');
        $select_map = [];
        if ($field_ids) {
            $in = implode(',', array_map('intval', $field_ids));
            $opt_rows = $wpdb->get_results("SELECT field_id,opt_value,opt_label,sort FROM {$this->options_table} WHERE field_id IN ($in) ORDER BY sort ASC", ARRAY_A) ?: [];
            foreach ($opt_rows as $o) {
                $select_map[$o['field_id']][] = [
                    'value' => $o['opt_value'],
                    'label' => $o['opt_label']
                ];
            }
        }
        $out = [];
        foreach ($rows as $r) {
            $cfg = json_decode($r['config_json'] ?? '[]', true) ?: [];
            $field = [
                'type'       => $r['type'],
                'name'       => $r['field_key'],
                'label'      => $r['label'],
                'required'   => (bool)$r['required'],
            ];
            $field = array_merge($field, $cfg);
            if ($r['type'] === 'select') {
                $field['options'] = $select_map[$r['id']] ?? [];
            }
            $out[] = $field;
        }
        return $out;
    }

    private function extract_config(array $field): array
    {
        // Keep non-core structural attributes for config; exclude type/name/label/required/options
        $config = $field;
        unset($config['type'], $config['name'], $config['label'], $config['required'], $config['options']);
        return $config;
    }
}
