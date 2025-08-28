<?php

namespace Anas\WCCRM\Forms\Versioning;

use Anas\WCCRM\Forms\FormRepository;

defined('ABSPATH') || exit;

/**
 * Handles version snapshots for forms (stub implementation).
 * Future: persist versions in custom table and allow rollback.
 */
class FormVersioningService
{
    private FormRepository $forms;

    public function __construct(FormRepository $forms)
    {
        $this->forms = $forms;
    }

    /**
     * Save a snapshot of the form (currently no-op placeholder).
     */
    public function snapshot(int $form_id, array $data): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wccrm_form_versions';
        if ($form_id <= 0) {
            return;
        }
        $payload = wp_json_encode([
            'fields' => $data['fields'] ?? [],
            'settings' => $data['settings'] ?? []
        ]);
        $wpdb->insert($table, [
            'form_id' => $form_id,
            'version' => time(),
            'snapshot_json' => $payload,
            'created_at' => current_time('mysql')
        ], ['%d', '%d', '%s', '%s']);
        do_action('wccrm_form_version_created', $form_id);
    }
}
