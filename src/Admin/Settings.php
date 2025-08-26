<?php
namespace WCP\Admin;

defined( 'ABSPATH' ) || exit;

class Settings {

    const OPTION_TOKENS = 'wcp_tokens';
    const PAGE_SLUG     = 'wcp-settings';

    public static function init(): void {
        add_action( 'admin_menu', [ __CLASS__, 'menu' ] );
        add_action( 'admin_init', [ __CLASS__, 'register' ] );
    }

    public static function menu(): void {
        add_menu_page(
            __( 'WooCommerce CRM', 'wcp' ),
            __( 'WC CRM', 'wcp' ),
            self::cap(),
            self::PAGE_SLUG,
            [ __CLASS__, 'render' ],
            'dashicons-groups',
            56
        );
    }

    protected static function cap(): string {
        return 'manage_options';
    }

    public static function register(): void {
        register_setting(
            'wcp_settings',
            self::OPTION_TOKENS,
            [
                'type'              => 'array',
                'sanitize_callback' => [ __CLASS__, 'sanitize_tokens' ],
                'default'           => []
            ]
        );

        add_settings_section(
            'wcp_integrations_section',
            __( 'Integrations', 'wcp' ),
            function () {
                echo '<p>' . esc_html__( 'Store API tokens and enable integrations. Leave a field empty to disable that integration.', 'wcp' ) . '</p>';
            },
            self::PAGE_SLUG
        );

        foreach ( self::fields() as $key => $field ) {
            add_settings_field(
                $key,
                esc_html( $field['label'] ),
                [ __CLASS__, 'field_input' ],
                self::PAGE_SLUG,
                'wcp_integrations_section',
                [ 'key' => $key, 'type' => $field['type'], 'placeholder' => $field['placeholder'] ?? '' ]
            );
        }
    }

    protected static function fields(): array {
        return [
            'hubspot'      => [ 'label' => __( 'HubSpot Private App Token', 'wcp' ), 'type' => 'password', 'placeholder' => 'pat-...' ],
            'zoho'         => [ 'label' => __( 'Zoho Access Token', 'wcp' ), 'type' => 'password', 'placeholder' => '1000.xxxxx' ],
            'google_drive' => [ 'label' => __( 'Google Drive API Token', 'wcp' ), 'type' => 'password', 'placeholder' => 'ya29.a0...' ],
            'whatsapp'     => [ 'label' => __( 'WhatsApp API Token', 'wcp' ), 'type' => 'password', 'placeholder' => 'EAA...' ],
        ];
    }

    public static function sanitize_tokens( $input ): array {
        $clean = [];
        if ( is_array( $input ) ) {
            $allowed = array_keys( self::fields() );
            foreach ( $allowed as $key ) {
                if ( empty( $input[ $key ] ) ) {
                    continue;
                }
                // Basic trimming only (tokens may contain symbols) – do not over-sanitize.
                $clean[ $key ] = trim( wp_unslash( $input[ $key ] ) );
            }
        }
        // Persist only supplied non-empty tokens.
        return $clean;
    }

    public static function field_input( array $args ): void {
        $tokens = get_option( self::OPTION_TOKENS, [] );
        $key    = $args['key'];
        $type   = $args['type'];
        $value  = isset( $tokens[ $key ] ) ? $tokens[ $key ] : '';
        $placeholder = $args['placeholder'];
        printf(
            '<input type="%1$s" id="%2$s" name="%3$s[%2$s]" value="%4$s" class="regular-text" placeholder="%5$s" autocomplete="off" />',
            esc_attr( $type ),
            esc_attr( $key ),
            esc_attr( self::OPTION_TOKENS ),
            esc_attr( $value ),
            esc_attr( $placeholder )
        );
        if ( $value ) {
            echo ' <span style="color:green;">' . esc_html__( 'Saved', 'wcp' ) . '</span>';
        }
    }

    public static function render(): void {
        if ( ! current_user_can( self::cap() ) ) {
            wp_die( esc_html__( 'Access denied.', 'wcp' ) );
        }
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'WooCommerce CRM – Settings', 'wcp' ) . '</h1>';

        // Simple status overview.
        $tokens = get_option( self::OPTION_TOKENS, [] );
        echo '<h2>' . esc_html__( 'Status', 'wcp' ) . '</h2><ul>';
        foreach ( self::fields() as $k => $f ) {
            $active = isset( $tokens[ $k ] ) ? '✔' : '–';
            printf(
                '<li><strong>%s:</strong> %s</li>',
                esc_html( $f['label'] ),
                $active === '✔' ? '<span style="color:green;">' . esc_html__( 'Active', 'wcp' ) . '</span>' : '<span style="color:#666;">' . esc_html__( 'Inactive', 'wcp' ) . '</span>'
            );
        }
        echo '</ul>';

        echo '<hr/><h2>' . esc_html__( 'API Tokens', 'wcp' ) . '</h2>';
        echo '<form method="post" action="options.php">';
        settings_fields( 'wcp_settings' );
        do_settings_sections( self::PAGE_SLUG );
        submit_button( __( 'Save Tokens', 'wcp' ) );
        echo '</form>';

        echo '<hr/><h2>' . esc_html__( 'Tools', 'wcp' ) . '</h2>';
        echo '<p>' . esc_html__( 'Programmatically ingest a social lead:', 'wcp' ) . '</p>';
        echo '<code>do_action( "wcp_ingest_social_lead", "facebook", [ "email" =&gt; "john@example.com", "name" =&gt; "John" ] );</code>';

        echo '</div>';
    }
}
