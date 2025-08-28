<?php
// Proper direct uninstall handler.
// WordPress includes this file automatically on uninstall if present.
if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Remove plugin options.
// Legacy + current options cleanup
delete_option('woocommerce_crm_plugin_options');
delete_option('wcp_version');
delete_option('wcp_tokens');
delete_option('wcp_api_key');
delete_option('universal_lead_capture_settings');
foreach (['ulc_enable_lead_capture', 'ulc_hubspot_api_key', 'ulc_zoho_api_key', 'ulc_google_ads_tracking_id', 'ulc_facebook_ads_pixel_id'] as $opt) {
    delete_option($opt);
}

// Drop custom tables (if they exist).
global $wpdb;
// Legacy tables
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}crm_leads");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}wcp_leads");
// Current plugin tables (complete data removal)
foreach (['wccrm_contacts', 'wccrm_message_templates', 'wccrm_message_queue', 'wccrm_contact_activity', 'wccrm_audit_log', 'wccrm_form_versions', 'wccrm_consent_log'] as $tbl) {
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}$tbl");
}

// Add any additional cleanup (transients, user meta, etc.) here.
