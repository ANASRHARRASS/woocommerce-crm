<?php

namespace Anas\WCCRM\Database;

defined('ABSPATH') || exit;

/**
 * Database installer and migration manager
 */
class Installer
{

    private const CURRENT_SCHEMA_VERSION = '2.1.0';
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

        // Extended CRM tables
        $this->create_message_templates_table($charset_collate);
        $this->create_message_queue_table($charset_collate);
        $this->create_contact_activity_table($charset_collate);
        $this->create_audit_log_table($charset_collate);
        $this->create_form_versions_table($charset_collate);
        $this->create_consent_log_table($charset_collate);

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
}
