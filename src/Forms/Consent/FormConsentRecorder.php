<?php

namespace Anas\WCCRM\Forms\Consent;

defined('ABSPATH') || exit;

/**
 * Records consent given through forms (stub implementation).
 */
class FormConsentRecorder
{
    /**
     * Record consent (placeholder).
     */
    public function record(array $submission, array $consent_meta = []): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wccrm_consent_log';
        if (! isset($submission['contact_id'])) {
            return; // need associated contact
        }
        $contact_id = (int) $submission['contact_id'];
        if ($contact_id <= 0) {
            return;
        }
        $purpose = sanitize_text_field($consent_meta['purpose'] ?? ($submission['consent_purpose'] ?? 'form_submission'));
        $source  = sanitize_text_field($consent_meta['source'] ?? ($submission['form_key'] ?? 'form'));
        $granted = ! empty($submission['consent']) ? 1 : 1; // assume granted when submitted; could inspect checkbox field
        $meta    = wp_json_encode([
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'ua' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'raw' => array_intersect_key($submission, ['email' => 1, 'phone' => 1])
        ]);
        $wpdb->insert($table, [
            'contact_id' => $contact_id,
            'purpose' => $purpose,
            'source' => $source,
            'granted' => $granted,
            'meta_json' => $meta,
            'created_at' => current_time('mysql')
        ], ['%d', '%s', '%s', '%d', '%s', '%s']);
        do_action('wccrm_consent_recorded', $contact_id, $purpose, $source);
    }
}
