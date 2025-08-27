<?php

namespace Anas\WCCRM\Database;

defined( 'ABSPATH' ) || exit;

/**
 * Database installer and migration manager
 */
class Installer {

    private const CURRENT_SCHEMA_VERSION = '2.0.0';
    private const SCHEMA_OPTION_KEY = 'wccrm_schema_version';

    public function maybe_upgrade(): void {
        $current_version = get_option( self::SCHEMA_OPTION_KEY, '0.0.0' );
        
        if ( version_compare( $current_version, self::CURRENT_SCHEMA_VERSION, '<' ) ) {
            $this->run_migrations( $current_version );
            update_option( self::SCHEMA_OPTION_KEY, self::CURRENT_SCHEMA_VERSION );
        }
    }

    protected function run_migrations( string $from_version ): void {
        global $wpdb;
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create all tables (dbDelta handles if they exist)
        $this->create_forms_table( $charset_collate );
        $this->create_contacts_table( $charset_collate );
        $this->create_contact_interests_table( $charset_collate );
        $this->create_form_submissions_table( $charset_collate );
        
        // Keep existing wcp_leads table for backward compatibility
        $this->ensure_leads_table( $charset_collate );
    }

    private function create_forms_table( string $charset_collate ): void {
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
        
        dbDelta( $sql );
    }

    private function create_contacts_table( string $charset_collate ): void {
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
        
        dbDelta( $sql );
    }

    private function create_contact_interests_table( string $charset_collate ): void {
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
        
        dbDelta( $sql );
    }

    private function create_form_submissions_table( string $charset_collate ): void {
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
        
        dbDelta( $sql );
    }

    private function ensure_leads_table( string $charset_collate ): void {
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
        
        dbDelta( $sql );
    }
}