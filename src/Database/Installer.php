<?php

namespace Anas\WCCRM\Database;

defined('ABSPATH') || exit;

/**
 * Database installer and migration manager
 */
class Installer
{

    // Bump when adding new tables or altering structure. 3.0.0 introduces multi‑phase expansion
    // (forms fields/options, variants, tags, external sync, social leads, conversations,
    // automations, daily metrics, channel preferences).
    private const CURRENT_SCHEMA_VERSION = '3.0.0';
    private const SCHEMA_OPTION_KEY = 'wccrm_schema_version';

    public function maybe_upgrade(): void
    {
        $current_version = get_option(self::SCHEMA_OPTION_KEY, '0.0.0');

        if (version_compare($current_version, self::CURRENT_SCHEMA_VERSION, '<')) {
            $this->run_migrations($current_version);
            update_option(self::SCHEMA_OPTION_KEY, self::CURRENT_SCHEMA_VERSION);
        }
    }

    protected function run_migrations(string $from_version): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        // Core tables
        $this->create_forms_table($charset_collate);
        $this->create_contacts_table($charset_collate);
        $this->create_contact_interests_table($charset_collate);
        $this->create_form_submissions_table($charset_collate);

    // Extended CRM tables (pre 3.0.0 set)
        $this->create_message_templates_table($charset_collate);
        $this->create_message_queue_table($charset_collate);
        $this->create_contact_activity_table($charset_collate);
        $this->create_audit_log_table($charset_collate);
        $this->create_form_versions_table($charset_collate);
        $this->create_consent_log_table($charset_collate);

    // 3.0.0 expansion tables
    $this->create_form_fields_table($charset_collate);
    $this->create_form_field_options_table($charset_collate);
    $this->create_form_product_variants_table($charset_collate);
    $this->create_contact_tags_table($charset_collate);
    $this->create_contact_tag_map_table($charset_collate);
    $this->create_external_sync_table($charset_collate);
    $this->create_social_leads_table($charset_collate);
    $this->create_conversations_table($charset_collate);
    $this->create_conversation_messages_table($charset_collate);
    $this->create_channel_preferences_table($charset_collate);
    $this->create_automations_table($charset_collate);
    $this->create_automation_runs_table($charset_collate);
    $this->create_daily_metrics_table($charset_collate);

        // Keep existing wcp_leads table for backward compatibility
        $this->ensure_leads_table($charset_collate);
    }

    private function create_forms_table(string $charset_collate): void
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wccrm_forms';

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            form_key VARCHAR(120) NOT NULL,
            name VARCHAR(190) NOT NULL,
            schema_json LONGTEXT NOT NULL,
            status VARCHAR(40) DEFAULT 'active',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY form_key (form_key),
            INDEX status (status),
            INDEX created_at (created_at)
        ) {$charset_collate};";

        dbDelta($sql);
    }

    private function create_contacts_table(string $charset_collate): void
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wccrm_contacts';

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(190) NULL,
            phone VARCHAR(50) NULL,
            first_name VARCHAR(190) NULL,
            last_name VARCHAR(190) NULL,
            status VARCHAR(40) DEFAULT 'active',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX email (email),
            INDEX phone (phone),
            INDEX status (status),
            INDEX created_at (created_at)
        ) {$charset_collate};";

        dbDelta($sql);
    }

    private function create_contact_interests_table(string $charset_collate): void
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wccrm_contact_interests';

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            contact_id BIGINT(20) UNSIGNED NOT NULL,
            interest_key VARCHAR(120) NOT NULL,
            weight INT DEFAULT 1,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY contact_interest (contact_id, interest_key),
            INDEX contact_id (contact_id),
            INDEX interest_key (interest_key),
            INDEX weight (weight)
        ) {$charset_collate};";

        dbDelta($sql);
    }

    private function create_form_submissions_table(string $charset_collate): void
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wccrm_form_submissions';

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            form_key VARCHAR(120) NOT NULL,
            contact_id BIGINT(20) UNSIGNED NULL,
            submission_json LONGTEXT NOT NULL,
            user_ip VARCHAR(45) NULL,
            user_agent VARCHAR(255) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX form_key (form_key),
            INDEX contact_id (contact_id),
            INDEX created_at (created_at)
        ) {$charset_collate};";

        dbDelta($sql);
    }

    private function ensure_leads_table(string $charset_collate): void
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wcp_leads';

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            source VARCHAR(50) NOT NULL,
            email VARCHAR(190) NULL,
            phone VARCHAR(50) NULL,
            name VARCHAR(190) NULL,
            payload LONGTEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX email (email),
            INDEX phone (phone),
            INDEX source (source),
            INDEX created_at (created_at)
        ) {$charset_collate};";

        dbDelta($sql);
    }

    private function create_message_templates_table(string $charset_collate): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wccrm_message_templates';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            template_key VARCHAR(120) NOT NULL,
            channel VARCHAR(40) NOT NULL DEFAULT 'email',
            subject VARCHAR(255) NULL,
            body LONGTEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY template_key (template_key),
            INDEX channel (channel)
        ) {$charset_collate};";
        dbDelta($sql);
    }

    private function create_message_queue_table(string $charset_collate): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wccrm_message_queue';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            contact_id BIGINT UNSIGNED NULL,
            channel VARCHAR(40) NOT NULL DEFAULT 'email',
            template_key VARCHAR(120) NULL,
            payload_json LONGTEXT NULL,
            status VARCHAR(40) NOT NULL DEFAULT 'pending',
            attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
            last_error TEXT NULL,
            scheduled_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX status (status),
            INDEX channel (channel),
            INDEX scheduled_at (scheduled_at),
            INDEX contact_id (contact_id)
        ) {$charset_collate};";
        dbDelta($sql);
    }

    private function create_contact_activity_table(string $charset_collate): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wccrm_contact_activity';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            contact_id BIGINT UNSIGNED NOT NULL,
            type VARCHAR(60) NOT NULL,
            ref_id BIGINT UNSIGNED NULL,
            meta_json LONGTEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX contact_id (contact_id),
            INDEX type (type),
            INDEX created_at (created_at)
        ) {$charset_collate};";
        dbDelta($sql);
    }

    private function create_audit_log_table(string $charset_collate): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wccrm_audit_log';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            action VARCHAR(120) NOT NULL,
            actor VARCHAR(120) NULL,
            context_json LONGTEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX action (action),
            INDEX created_at (created_at)
        ) {$charset_collate};";
        dbDelta($sql);
    }

    private function create_form_versions_table(string $charset_collate): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wccrm_form_versions';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            form_id BIGINT UNSIGNED NOT NULL,
            version INT UNSIGNED NOT NULL,
            snapshot_json LONGTEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY form_version (form_id, version),
            INDEX form_id (form_id)
        ) {$charset_collate};";
        dbDelta($sql);
    }

    private function create_consent_log_table(string $charset_collate): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wccrm_consent_log';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            contact_id BIGINT UNSIGNED NULL,
            form_key VARCHAR(120) NULL,
            channel VARCHAR(40) NOT NULL,
            granted TINYINT(1) NOT NULL DEFAULT 1,
            meta_json LONGTEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX contact_id (contact_id),
            INDEX channel (channel),
            INDEX form_key (form_key),
            INDEX created_at (created_at)
        ) {$charset_collate};";
        dbDelta($sql);
    }

    private function create_form_fields_table(string $charset_collate): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wccrm_form_fields';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            form_id BIGINT UNSIGNED NOT NULL,
            field_key VARCHAR(120) NOT NULL,
            type VARCHAR(60) NOT NULL,
            label VARCHAR(190) NULL,
            position INT UNSIGNED NOT NULL DEFAULT 0,
            config_json LONGTEXT NULL,
            required TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY form_field (form_id, field_key),
            INDEX form_id (form_id),
            INDEX type (type)
        ) {$charset_collate};";
        dbDelta($sql);
    }

    private function create_form_field_options_table(string $charset_collate): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wccrm_form_field_options';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            field_id BIGINT UNSIGNED NOT NULL,
            opt_value VARCHAR(190) NOT NULL,
            opt_label VARCHAR(190) NULL,
            sort INT UNSIGNED NOT NULL DEFAULT 0,
            UNIQUE KEY field_value (field_id, opt_value),
            INDEX field_id (field_id)
        ) {$charset_collate};";
        dbDelta($sql);
    }

    private function create_form_product_variants_table(string $charset_collate): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wccrm_form_product_variants';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            form_id BIGINT UNSIGNED NOT NULL,
            product_id BIGINT UNSIGNED NOT NULL,
            variant_schema_json LONGTEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY form_product (form_id, product_id),
            INDEX product_id (product_id)
        ) {$charset_collate};";
        dbDelta($sql);
    }

    private function create_contact_tags_table(string $charset_collate): void
    {
        global $wpdb; $table = $wpdb->prefix . 'wccrm_contact_tags';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            tag_key VARCHAR(120) NOT NULL,
            name VARCHAR(190) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY tag_key (tag_key)
        ) {$charset_collate};";
        dbDelta($sql);
    }

    private function create_contact_tag_map_table(string $charset_collate): void
    {
        global $wpdb; $table = $wpdb->prefix . 'wccrm_contact_tag_map';
        $sql = "CREATE TABLE {$table} (
            contact_id BIGINT UNSIGNED NOT NULL,
            tag_id BIGINT UNSIGNED NOT NULL,
            added_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (contact_id, tag_id),
            INDEX tag_id (tag_id)
        ) {$charset_collate};";
        dbDelta($sql);
    }

    private function create_external_sync_table(string $charset_collate): void
    {
        global $wpdb; $table = $wpdb->prefix . 'wccrm_external_sync';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            contact_id BIGINT UNSIGNED NOT NULL,
            system VARCHAR(60) NOT NULL,
            external_id VARCHAR(190) NULL,
            status VARCHAR(40) NOT NULL DEFAULT 'pending',
            last_error TEXT NULL,
            last_synced_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY contact_system (contact_id, system),
            INDEX system (system),
            INDEX status (status)
        ) {$charset_collate};";
        dbDelta($sql);
    }

    private function create_social_leads_table(string $charset_collate): void
    {
        global $wpdb; $table = $wpdb->prefix . 'wccrm_social_leads';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            source VARCHAR(40) NOT NULL,
            external_lead_id VARCHAR(190) NULL,
            normalized_email VARCHAR(190) NULL,
            normalized_phone VARCHAR(50) NULL,
            payload_json LONGTEXT NULL,
            processed TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY source_external (source, external_lead_id),
            INDEX source (source),
            INDEX processed (processed)
        ) {$charset_collate};";
        dbDelta($sql);
    }

    private function create_conversations_table(string $charset_collate): void
    {
        global $wpdb; $table = $wpdb->prefix . 'wccrm_conversations';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            contact_id BIGINT UNSIGNED NOT NULL,
            channel VARCHAR(40) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'open',
            last_message_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX contact_id (contact_id),
            INDEX channel (channel),
            INDEX status (status),
            INDEX last_message_at (last_message_at)
        ) {$charset_collate};";
        dbDelta($sql);
    }

    private function create_conversation_messages_table(string $charset_collate): void
    {
        global $wpdb; $table = $wpdb->prefix . 'wccrm_conversation_messages';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            conversation_id BIGINT UNSIGNED NOT NULL,
            direction VARCHAR(5) NOT NULL,
            body LONGTEXT NULL,
            template_key VARCHAR(120) NULL,
            media_json LONGTEXT NULL,
            status VARCHAR(40) NOT NULL DEFAULT 'sent',
            error TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX conversation_id (conversation_id),
            INDEX direction (direction),
            INDEX template_key (template_key)
        ) {$charset_collate};";
        dbDelta($sql);
    }

    private function create_channel_preferences_table(string $charset_collate): void
    {
        global $wpdb; $table = $wpdb->prefix . 'wccrm_channel_preferences';
        $sql = "CREATE TABLE {$table} (
            contact_id BIGINT UNSIGNED NOT NULL,
            channel VARCHAR(40) NOT NULL,
            subscribed TINYINT(1) NOT NULL DEFAULT 1,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (contact_id, channel),
            INDEX subscribed (subscribed)
        ) {$charset_collate};";
        dbDelta($sql);
    }

    private function create_automations_table(string $charset_collate): void
    {
        global $wpdb; $table = $wpdb->prefix . 'wccrm_automations';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(190) NOT NULL,
            trigger VARCHAR(60) NOT NULL,
            conditions_json LONGTEXT NULL,
            actions_json LONGTEXT NOT NULL,
            active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX trigger (trigger),
            INDEX active (active)
        ) {$charset_collate};";
        dbDelta($sql);
    }

    private function create_automation_runs_table(string $charset_collate): void
    {
        global $wpdb; $table = $wpdb->prefix . 'wccrm_automation_runs';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            automation_id BIGINT UNSIGNED NOT NULL,
            target_id BIGINT UNSIGNED NULL,
            status VARCHAR(40) NOT NULL DEFAULT 'pending',
            log_json LONGTEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX automation_id (automation_id),
            INDEX status (status)
        ) {$charset_collate};";
        dbDelta($sql);
    }

    private function create_daily_metrics_table(string $charset_collate): void
    {
        global $wpdb; $table = $wpdb->prefix . 'wccrm_daily_metrics';
        $sql = "CREATE TABLE {$table} (
            metric_date DATE NOT NULL,
            new_contacts INT UNSIGNED NOT NULL DEFAULT 0,
            submissions INT UNSIGNED NOT NULL DEFAULT 0,
            msgs_out INT UNSIGNED NOT NULL DEFAULT 0,
            msgs_in INT UNSIGNED NOT NULL DEFAULT 0,
            whatsapp_out INT UNSIGNED NOT NULL DEFAULT 0,
            avg_first_response_seconds INT UNSIGNED NULL,
            sources_json LONGTEXT NULL,
            PRIMARY KEY (metric_date)
        ) {$charset_collate};";
        dbDelta($sql);
    }
}
