<?php

namespace WooCommerceCRMPlugin\Public;

class Shortcodes {
    public function __construct() {
        add_shortcode('crm_contact_form', [$this, 'renderContactForm']);
        add_shortcode('crm_reseller_form', [$this, 'renderResellerForm']);
        add_shortcode('crm_dynamic_form', [$this, 'renderDynamicForm']);
    }

    public function renderContactForm($atts) {
        // Logic to render the contact form
        ob_start();
        include(plugin_dir_path(__FILE__) . '../../templates/public/form-contact.php');
        return ob_get_clean();
    }

    public function renderResellerForm($atts) {
        // Logic to render the reseller form
        ob_start();
        include(plugin_dir_path(__FILE__) . '../../templates/public/form-reseller.php');
        return ob_get_clean();
    }

    public function renderDynamicForm($atts) {
        // Logic to render the dynamic form based on WooCommerce product attributes
        ob_start();
        include(plugin_dir_path(__FILE__) . '../../templates/public/form-dynamic.php');
        return ob_get_clean();
    }
}