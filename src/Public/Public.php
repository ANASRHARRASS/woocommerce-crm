<?php

namespace WooCommerceCRMPlugin\Public;

class PublicController {
    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_shortcode('crm_contact_form', [$this, 'render_contact_form']);
        add_shortcode('crm_reseller_form', [$this, 'render_reseller_form']);
        add_shortcode('crm_dynamic_form', [$this, 'render_dynamic_form']);
    }

    public function enqueue_scripts() {
        wp_enqueue_style('crm-public-style', plugin_dir_url(__FILE__) . '../../assets/css/public.css');
        wp_enqueue_script('crm-public-script', plugin_dir_url(__FILE__) . '../../assets/js/public.js', ['jquery'], null, true);
    }

    public function render_contact_form() {
        ob_start();
        include plugin_dir_path(__FILE__) . '../../templates/public/form-contact.php';
        return ob_get_clean();
    }

    public function render_reseller_form() {
        ob_start();
        include plugin_dir_path(__FILE__) . '../../templates/public/form-reseller.php';
        return ob_get_clean();
    }

    public function render_dynamic_form() {
        ob_start();
        include plugin_dir_path(__FILE__) . '../../templates/public/form-dynamic.php';
        return ob_get_clean();
    }
}