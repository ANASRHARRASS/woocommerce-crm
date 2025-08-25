<?php
// filepath: /universal-lead-capture-plugin/universal-lead-capture-plugin/src/integrations/elementor/class-elementor.php

class ElementorIntegration {
    private $plugin_name;

    public function __construct($plugin_name) {
        $this->plugin_name = $plugin_name;
        add_action('elementor/widgets/widgets_registered', [$this, 'register_widgets']);
    }

    public function register_widgets() {
        // Register custom Elementor widgets here
        require_once plugin_dir_path(__FILE__) . 'widgets/class-lead-capture-widget.php';
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new \Lead_Capture_Widget());
    }

    public function enqueue_scripts() {
        // Enqueue necessary scripts and styles for Elementor integration
        wp_enqueue_script($this->plugin_name . '-elementor', plugin_dir_url(__FILE__) . 'assets/js/elementor.js', ['jquery'], null, true);
        wp_enqueue_style($this->plugin_name . '-elementor', plugin_dir_url(__FILE__) . 'assets/css/elementor.css');
    }
}
?>