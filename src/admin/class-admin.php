<?php
// filepath: /universal-lead-capture-plugin/universal-lead-capture-plugin/src/admin/class-admin.php

class Admin {
    private $settings;

    public function __construct() {
        $this->settings = get_option('universal_lead_capture_settings');
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'settings_init']);
    // Handle admin test actions
    add_action('admin_post_ulc_test_hubspot', [$this, 'handle_test_hubspot']);
    }

    public function add_admin_menu() {
        add_menu_page(
            'Universal Lead Capture',
            'Lead Capture',
            'manage_options',
            'universal_lead_capture',
            [$this, 'settings_page']
        );
    }

    public function settings_init() {
        register_setting('pluginPage', 'universal_lead_capture_settings', [$this, 'sanitize_settings']);

        add_settings_section(
            'universal_lead_capture_pluginPage_section',
            __('Settings for Universal Lead Capture Plugin', 'universal-lead-capture-plugin'),
            null,
            'pluginPage'
        );

        // HubSpot
        add_settings_field(
            'hubspot_api_key',
            __('HubSpot API Key', 'universal-lead-capture-plugin'),
            [$this, 'hubspot_api_key_render'],
            'pluginPage',
            'universal_lead_capture_pluginPage_section'
        );

        // Zoho
        add_settings_field(
            'zoho_api_url',
            __('Zoho API URL', 'universal-lead-capture-plugin'),
            [$this, 'zoho_api_url_render'],
            'pluginPage',
            'universal_lead_capture_pluginPage_section'
        );
        add_settings_field(
            'zoho_api_key',
            __('Zoho API Key', 'universal-lead-capture-plugin'),
            [$this, 'zoho_api_key_render'],
            'pluginPage',
            'universal_lead_capture_pluginPage_section'
        );

        // Google Ads
        add_settings_field(
            'google_ads_api_key',
            __('Google Ads API Key', 'universal-lead-capture-plugin'),
            [$this, 'google_ads_api_key_render'],
            'pluginPage',
            'universal_lead_capture_pluginPage_section'
        );

        // Facebook Ads
        add_settings_field(
            'facebook_app_id',
            __('Facebook App ID', 'universal-lead-capture-plugin'),
            [$this, 'facebook_app_id_render'],
            'pluginPage',
            'universal_lead_capture_pluginPage_section'
        );
        add_settings_field(
            'facebook_app_secret',
            __('Facebook App Secret', 'universal-lead-capture-plugin'),
            [$this, 'facebook_app_secret_render'],
            'pluginPage',
            'universal_lead_capture_pluginPage_section'
        );
        add_settings_field(
            'facebook_access_token',
            __('Facebook Access Token', 'universal-lead-capture-plugin'),
            [$this, 'facebook_access_token_render'],
            'pluginPage',
            'universal_lead_capture_pluginPage_section'
        );

        // Elementor
        add_settings_field(
            'elementor_plugin_name',
            __('Elementor Plugin Name (handle)', 'universal-lead-capture-plugin'),
            [$this, 'elementor_plugin_name_render'],
            'pluginPage',
            'universal_lead_capture_pluginPage_section'
        );
    }

    public function sanitize_settings($input) {
        $out = [];
        $out['hubspot_api_key'] = isset($input['hubspot_api_key']) ? sanitize_text_field($input['hubspot_api_key']) : '';
        $out['zoho_api_url'] = isset($input['zoho_api_url']) ? esc_url_raw($input['zoho_api_url']) : '';
        $out['zoho_api_key'] = isset($input['zoho_api_key']) ? sanitize_text_field($input['zoho_api_key']) : '';
        $out['google_ads_api_key'] = isset($input['google_ads_api_key']) ? sanitize_text_field($input['google_ads_api_key']) : '';
        $out['facebook_app_id'] = isset($input['facebook_app_id']) ? sanitize_text_field($input['facebook_app_id']) : '';
        $out['facebook_app_secret'] = isset($input['facebook_app_secret']) ? sanitize_text_field($input['facebook_app_secret']) : '';
        $out['facebook_access_token'] = isset($input['facebook_access_token']) ? sanitize_text_field($input['facebook_access_token']) : '';
        $out['elementor_plugin_name'] = isset($input['elementor_plugin_name']) ? sanitize_text_field($input['elementor_plugin_name']) : '';
        // Preserve any other fields safely
        return $out;
    }

    public function text_field_render() {
        $options = get_option('universal_lead_capture_settings');
        ?>
        <input type='text' name='universal_lead_capture_settings[universal_lead_capture_text_field]' value='<?php echo $options['universal_lead_capture_text_field']; ?>'>
        <?php
    }

    public function hubspot_api_key_render() {
        $options = get_option('universal_lead_capture_settings');
        ?>
        <input type='text' name='universal_lead_capture_settings[hubspot_api_key]' value='<?php echo isset($options['hubspot_api_key']) ? esc_attr($options['hubspot_api_key']) : ''; ?>' class='regular-text'>
        <?php
    }

    public function zoho_api_url_render() {
        $options = get_option('universal_lead_capture_settings');
        ?>
        <input type='url' name='universal_lead_capture_settings[zoho_api_url]' value='<?php echo isset($options['zoho_api_url']) ? esc_attr($options['zoho_api_url']) : ''; ?>' class='regular-text'>
        <?php
    }

    public function zoho_api_key_render() {
        $options = get_option('universal_lead_capture_settings');
        ?>
        <input type='text' name='universal_lead_capture_settings[zoho_api_key]' value='<?php echo isset($options['zoho_api_key']) ? esc_attr($options['zoho_api_key']) : ''; ?>' class='regular-text'>
        <?php
    }

    public function google_ads_api_key_render() {
        $options = get_option('universal_lead_capture_settings');
        ?>
        <input type='text' name='universal_lead_capture_settings[google_ads_api_key]' value='<?php echo isset($options['google_ads_api_key']) ? esc_attr($options['google_ads_api_key']) : ''; ?>' class='regular-text'>
        <?php
    }

    public function facebook_app_id_render() {
        $options = get_option('universal_lead_capture_settings');
        ?>
        <input type='text' name='universal_lead_capture_settings[facebook_app_id]' value='<?php echo isset($options['facebook_app_id']) ? esc_attr($options['facebook_app_id']) : ''; ?>' class='regular-text'>
        <?php
    }

    public function facebook_app_secret_render() {
        $options = get_option('universal_lead_capture_settings');
        ?>
        <input type='text' name='universal_lead_capture_settings[facebook_app_secret]' value='<?php echo isset($options['facebook_app_secret']) ? esc_attr($options['facebook_app_secret']) : ''; ?>' class='regular-text'>
        <?php
    }

    public function facebook_access_token_render() {
        $options = get_option('universal_lead_capture_settings');
        ?>
        <input type='text' name='universal_lead_capture_settings[facebook_access_token]' value='<?php echo isset($options['facebook_access_token']) ? esc_attr($options['facebook_access_token']) : ''; ?>' class='regular-text'>
        <?php
    }

    public function elementor_plugin_name_render() {
        $options = get_option('universal_lead_capture_settings');
        ?>
        <input type='text' name='universal_lead_capture_settings[elementor_plugin_name]' value='<?php echo isset($options['elementor_plugin_name']) ? esc_attr($options['elementor_plugin_name']) : ''; ?>' class='regular-text'>
        <?php
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h2>Universal Lead Capture Plugin</h2>

            <?php if ( isset( $_GET['ulc_test_hubspot'] ) ) :
                $s = esc_attr( $_GET['ulc_test_hubspot'] );
                if ( 'success' === $s ) : ?>
                    <div class="notice notice-success is-dismissible"><p>HubSpot test lead sent successfully.</p></div>
                <?php else : ?>
                    <div class="notice notice-error is-dismissible"><p>HubSpot test failed: <?php echo esc_html( $s ); ?></p></div>
                <?php endif;
            endif; ?>

            <form action='options.php' method='post'>
                <?php
                settings_fields('pluginPage');
                do_settings_sections('pluginPage');
                submit_button();
                ?>
            </form>

            <h3>Test integrations</h3>
            <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
                <?php wp_nonce_field('ulc_test_hubspot'); ?>
                <input type="hidden" name="action" value="ulc_test_hubspot">
                <?php submit_button('Test HubSpot Connection'); ?>
            </form>
        </div>
        <?php
    }

    public function handle_test_hubspot() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Insufficient permissions' );
        }
        check_admin_referer( 'ulc_test_hubspot' );

        // Attempt to obtain HubSpot integration via IntegrationManager
        if ( ! class_exists( 'IntegrationManager' ) ) {
            $redirect = add_query_arg( 'ulc_test_hubspot', 'integration_manager_missing', admin_url( 'admin.php?page=universal_lead_capture' ) );
            wp_safe_redirect( $redirect );
            exit;
        }

        $hub = IntegrationManager::get( 'hubspot' );
        if ( ! $hub ) {
            $redirect = add_query_arg( 'ulc_test_hubspot', 'not_configured', admin_url( 'admin.php?page=universal_lead_capture' ) );
            wp_safe_redirect( $redirect );
            exit;
        }

        try {
            if ( method_exists( $hub, 'captureLead' ) ) {
                $test = [ 'email' => 'test+' . time() . '@example.com', 'firstname' => 'ULC', 'lastname' => 'Test' ];
                $res = $hub->captureLead( $test );
                $redirect = add_query_arg( 'ulc_test_hubspot', 'success', admin_url( 'admin.php?page=universal_lead_capture' ) );
                wp_safe_redirect( $redirect );
                exit;
            } else {
                $redirect = add_query_arg( 'ulc_test_hubspot', 'no_capture_method', admin_url( 'admin.php?page=universal_lead_capture' ) );
                wp_safe_redirect( $redirect );
                exit;
            }
        } catch ( Exception $e ) {
            $redirect = add_query_arg( 'ulc_test_hubspot', urlencode( $e->getMessage() ), admin_url( 'admin.php?page=universal_lead_capture' ) );
            wp_safe_redirect( $redirect );
            exit;
        }
    }
}
?>