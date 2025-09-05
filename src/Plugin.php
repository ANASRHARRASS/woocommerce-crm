<?php

namespace KSCRM;

defined('ABSPATH') || exit;

/**
 * Main Plugin Class for WooCommerce CRM v0.3.0
 */
class Plugin {
    
    private static $instance = null;
    
    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {}
    
    public function init() {
        $this->init_hooks();
        $this->init_services();
        $this->init_shortcodes();
        $this->init_rest_api();
        $this->init_admin();
        $this->init_assets();
        $this->init_elementor();
    }
    
    private function init_hooks() {
        // Order hooks for UTM tracking
        add_action('woocommerce_checkout_order_processed', [$this, 'track_order_utm'], 10, 1);
        
        // Cart link handler
        add_action('init', [$this, 'handle_cart_links']);
        
        // Admin menu
        add_action('admin_menu', [$this, 'add_admin_menu']);
        
        // AJAX handlers
        add_action('wp_ajax_kscrm_quick_lead', [$this, 'handle_quick_lead']);
        add_action('wp_ajax_nopriv_kscrm_lead_form', [$this, 'handle_lead_form_submission']);
        add_action('wp_ajax_kscrm_lead_form', [$this, 'handle_lead_form_submission']);
        add_action('wp_ajax_nopriv_kscrm_contact_form', [$this, 'handle_contact_form_submission']);
        add_action('wp_ajax_kscrm_contact_form', [$this, 'handle_contact_form_submission']);
        add_action('wp_ajax_kscrm_product_search', [$this, 'handle_product_search']);
    }
    
    private function init_services() {
        // UTM Stats tracking
        require_once KSCRM_PLUGIN_DIR . 'src/Analytics/UTM_Stats.php';
        
        // WhatsApp utilities
        require_once KSCRM_PLUGIN_DIR . 'src/WhatsApp/WhatsApp_Utils.php';
        
        // Cart handler
        require_once KSCRM_PLUGIN_DIR . 'src/Cart/Cart_Link_Handler.php';
        new \KSCRM\Cart\Cart_Link_Handler();
        
        // Lead management
        require_once KSCRM_PLUGIN_DIR . 'src/Admin/Lead_Columns.php';
        new \KSCRM\Admin\Lead_Columns();
    }
    
    private function init_shortcodes() {
        require_once KSCRM_PLUGIN_DIR . 'src/Frontend/Shortcode_Lead_Form.php';
        require_once KSCRM_PLUGIN_DIR . 'src/Frontend/Shortcode_Contact_Form.php';
        require_once KSCRM_PLUGIN_DIR . 'src/Frontend/Shortcode_WhatsApp_Product.php';
        
        add_shortcode('kscrm_lead_form', ['\KSCRM\Frontend\Shortcode_Lead_Form', 'render']);
        add_shortcode('kscrm_contact_form', ['\KSCRM\Frontend\Shortcode_Contact_Form', 'render']);
        add_shortcode('kscrm_whatsapp_product', ['\KSCRM\Frontend\Shortcode_WhatsApp_Product', 'render']);
    }
    
    private function init_rest_api() {
        add_action('rest_api_init', function() {
            // Analytics endpoints
            require_once KSCRM_PLUGIN_DIR . 'src/Analytics/UTM_Stats.php';
            \KSCRM\Analytics\UTM_Stats::register_rest_routes();
            
            // Product search endpoint
            require_once KSCRM_PLUGIN_DIR . 'src/Products/Product_Search_REST.php';
            \KSCRM\Products\Product_Search_REST::register_rest_routes();
        });
    }
    
    private function init_admin() {
        if (is_admin()) {
            require_once KSCRM_PLUGIN_DIR . 'src/Admin/Dashboard.php';
        }
    }
    
    private function init_assets() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }
    
    private function init_elementor() {
        add_action('elementor/widgets/widgets_registered', function() {
            if (class_exists('\Elementor\Plugin')) {
                require_once KSCRM_PLUGIN_DIR . 'src/Integrations/Elementor/Elementor_Init.php';
                new \KSCRM\Integrations\Elementor\Elementor_Init();
            }
        });
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('WooCommerce CRM', 'woocommerce-crm-plugin'),
            __('Woo CRM', 'woocommerce-crm-plugin'),
            'manage_woocommerce',
            'kscrm-dashboard',
            [$this, 'render_dashboard'],
            'dashicons-chart-line',
            30
        );
        
        add_submenu_page(
            'kscrm-dashboard',
            __('Dashboard', 'woocommerce-crm-plugin'),
            __('Dashboard', 'woocommerce-crm-plugin'),
            'manage_woocommerce',
            'kscrm-dashboard'
        );
        
        add_submenu_page(
            'kscrm-dashboard',
            __('Leads', 'woocommerce-crm-plugin'),
            __('Leads', 'woocommerce-crm-plugin'),
            'manage_woocommerce',
            'edit.php?post_type=kscrm_lead'
        );
    }
    
    public function render_dashboard() {
        require_once KSCRM_PLUGIN_DIR . 'src/Admin/Dashboard.php';
        \KSCRM\Admin\Dashboard::render();
    }
    
    public function track_order_utm($order_id) {
        require_once KSCRM_PLUGIN_DIR . 'src/Analytics/UTM_Stats.php';
        \KSCRM\Analytics\UTM_Stats::track_order($order_id);
    }
    
    public function handle_cart_links() {
        require_once KSCRM_PLUGIN_DIR . 'src/Cart/Cart_Link_Handler.php';
        \KSCRM\Cart\Cart_Link_Handler::handle_request();
    }
    
    public function handle_quick_lead() {
        check_ajax_referer('kscrm_dashboard_nonce', 'nonce');
        
        $name = sanitize_text_field($_POST['name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        
        if (empty($name) || empty($email)) {
            wp_send_json_error(['message' => __('Name and email are required.', 'woocommerce-crm-plugin')]);
        }
        
        // Create lead
        $lead_data = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'source' => 'dashboard-quick',
            'utm_source' => 'admin-dashboard',
            'created_at' => current_time('mysql')
        ];
        
        $this->create_lead($lead_data);
        
        wp_send_json_success(['message' => __('Lead created successfully!', 'woocommerce-crm-plugin')]);
    }
    
    public function handle_lead_form_submission() {
        check_ajax_referer('kscrm_lead_form_nonce', 'nonce');
        
        $name = sanitize_text_field($_POST['name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $utm_source = sanitize_text_field($_POST['utm_source'] ?? '');
        $utm_medium = sanitize_text_field($_POST['utm_medium'] ?? '');
        $utm_campaign = sanitize_text_field($_POST['utm_campaign'] ?? '');
        
        if (empty($name) || empty($email)) {
            wp_send_json_error(['message' => __('Name and email are required.', 'woocommerce-crm-plugin')]);
        }
        
        $lead_data = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'source' => 'lead-form',
            'utm_source' => $utm_source,
            'utm_medium' => $utm_medium,
            'utm_campaign' => $utm_campaign,
            'created_at' => current_time('mysql')
        ];
        
        $this->create_lead($lead_data);
        
        wp_send_json_success(['message' => __('Thank you! Your information has been submitted.', 'woocommerce-crm-plugin')]);
    }
    
    public function handle_contact_form_submission() {
        check_ajax_referer('kscrm_contact_form_nonce', 'nonce');
        
        $name = sanitize_text_field($_POST['name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $product_id = intval($_POST['product_id'] ?? 0);
        
        if (empty($name) || empty($email)) {
            wp_send_json_error(['message' => __('Name and email are required.', 'woocommerce-crm-plugin')]);
        }
        
        $lead_data = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'message' => $message,
            'source' => 'contact-form',
            'product_id' => $product_id,
            'created_at' => current_time('mysql')
        ];
        
        $this->create_lead($lead_data);
        
        wp_send_json_success(['message' => __('Thank you! We will contact you soon.', 'woocommerce-crm-plugin')]);
    }
    
    public function handle_product_search() {
        require_once KSCRM_PLUGIN_DIR . 'src/Products/Product_Search_REST.php';
        \KSCRM\Products\Product_Search_REST::handle_ajax_search();
    }
    
    private function create_lead($data) {
        global $wpdb;
        
        // Create leads table if it doesn't exist
        $table_name = $wpdb->prefix . 'kscrm_leads';
        
        $wpdb->insert(
            $table_name,
            $data,
            array_fill(0, count($data), '%s')
        );
        
        return $wpdb->insert_id;
    }
    
    public function enqueue_frontend_scripts() {
        wp_enqueue_style(
            'kscrm-frontend',
            KSCRM_PLUGIN_URL . 'assets/css/frontend.css',
            [],
            KSCRM_VERSION
        );
        
        wp_enqueue_script(
            'kscrm-forms',
            KSCRM_PLUGIN_URL . 'assets/js/forms.js',
            ['jquery'],
            KSCRM_VERSION,
            true
        );
        
        wp_localize_script('kscrm-forms', 'kscrm_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'lead_form_nonce' => wp_create_nonce('kscrm_lead_form_nonce'),
            'contact_form_nonce' => wp_create_nonce('kscrm_contact_form_nonce'),
        ]);
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'kscrm-dashboard') !== false) {
            wp_enqueue_style(
                'kscrm-admin',
                KSCRM_PLUGIN_URL . 'assets/css/admin.css',
                [],
                KSCRM_VERSION
            );
            
            wp_enqueue_script(
                'kscrm-chart',
                KSCRM_PLUGIN_URL . 'assets/js/chart.min.js',
                [],
                KSCRM_VERSION,
                true
            );
            
            wp_enqueue_script(
                'kscrm-dashboard',
                KSCRM_PLUGIN_URL . 'assets/js/dashboard.js',
                ['jquery', 'kscrm-chart'],
                KSCRM_VERSION,
                true
            );
            
            wp_localize_script('kscrm-dashboard', 'kscrm_dashboard', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'rest_url' => rest_url('ks-crm/v1/'),
                'nonce' => wp_create_nonce('kscrm_dashboard_nonce'),
                'rest_nonce' => wp_create_nonce('wp_rest'),
            ]);
        }
    }
}