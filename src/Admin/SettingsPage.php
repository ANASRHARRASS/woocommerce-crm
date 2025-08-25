<?php

namespace WooCommerceCRMPlugin\Admin;

class SettingsPage {
    private $plugin_name;
    private $version;

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    public function add_plugin_admin_menu() {
        add_menu_page(
            'WooCommerce CRM Settings',
            'CRM Settings',
            'manage_options',
            'woocommerce_crm_plugin',
            array( $this, 'create_admin_page' ),
            'dashicons-admin-generic',
            100
        );
    }

    public function create_admin_page() {
        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ), 'Permission Error', array( 'response' => 403 ) );
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'woocommerce_crm_plugin_options_group' );
                do_settings_sections( 'woocommerce_crm_plugin' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function page_init() {
        register_setting(
            'woocommerce_crm_plugin_options_group',
            'woocommerce_crm_plugin_options',
            array( $this, 'sanitize' )
        );

        add_settings_section(
            'setting_section_id',
            'CRM Integration Settings',
            array( $this, 'print_section_info' ),
            'woocommerce_crm_plugin'
        );

        add_settings_field(
            'hubspot_api_key',
            'HubSpot API Key',
            array( $this, 'hubspot_api_key_callback' ),
            'woocommerce_crm_plugin',
            'setting_section_id'
        );

        add_settings_field(
            'zoho_api_key',
            'Zoho API Key',
            array( $this, 'zoho_api_key_callback' ),
            'woocommerce_crm_plugin',
            'setting_section_id'
        );
    }

    public function sanitize( $input ) {
        $new_input = array();
        if( isset( $input['hubspot_api_key'] ) )
            $new_input['hubspot_api_key'] = sanitize_text_field( $input['hubspot_api_key'] );

        if( isset( $input['zoho_api_key'] ) )
            $new_input['zoho_api_key'] = sanitize_text_field( $input['zoho_api_key'] );

        return $new_input;
    }

    public function print_section_info() {
        print 'Enter your settings below:';
    }

    public function hubspot_api_key_callback() {
        $options = get_option( 'woocommerce_crm_plugin_options' );
        $value = isset( $options['hubspot_api_key'] ) ? $options['hubspot_api_key'] : '';
        printf(
            '<input type="password" id="hubspot_api_key" name="woocommerce_crm_plugin_options[hubspot_api_key]" value="%s" class="regular-text" />',
            esc_attr( $value )
        );
        echo '<p class="description">Enter your HubSpot API key. This will be stored securely.</p>';
    }

    public function zoho_api_key_callback() {
        $options = get_option( 'woocommerce_crm_plugin_options' );
        $value = isset( $options['zoho_api_key'] ) ? $options['zoho_api_key'] : '';
        printf(
            '<input type="password" id="zoho_api_key" name="woocommerce_crm_plugin_options[zoho_api_key]" value="%s" class="regular-text" />',
            esc_attr( $value )
        );
        echo '<p class="description">Enter your Zoho API key. This will be stored securely.</p>';
    }
}