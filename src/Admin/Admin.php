<?php

namespace WooCommerceCRMPlugin\Admin;

class Admin {
    public function __construct() {
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('admin_init', [$this, 'initializeSettings']);
    }

    public function addAdminMenu() {
        add_menu_page(
            'WooCommerce CRM',
            'WooCommerce CRM',
            'manage_options',
            'woocommerce-crm',
            [$this, 'renderAdminPage'],
            'dashicons-admin-generic',
            6
        );
    }

    public function renderAdminPage() {
        // Include the settings page template
        include_once plugin_dir_path(__FILE__) . 'SettingsPage.php';
    }

    public function initializeSettings() {
        // Register settings, sections, and fields here
    }
}