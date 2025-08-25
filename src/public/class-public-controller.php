<?php
/**
 * Class Public_Controller
 *
 * Public-facing functionality for the Universal Lead Capture Plugin.
 */
class Public_Controller {
    /**
     * Constructor
     */
    public function __construct() {
        // Initialize the plugin's public functionalities
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_shortcode('lead_capture_form', [$this, 'render_lead_capture_form']);
    }

    /**
     * Enqueue public scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_style('ulc-public-style', plugin_dir_url(__FILE__) . '../../assets/css/public.css');
        wp_enqueue_script('ulc-public-script', plugin_dir_url(__FILE__) . '../../assets/js/public.js', ['jquery'], null, true);
    }

    /**
     * Render the lead capture form
     *
     * @return string
     */
    public function render_lead_capture_form() {
        ob_start();
        include plugin_dir_path(__FILE__) . 'templates/form-template.php';
        return ob_get_clean();
    }
}
?>
