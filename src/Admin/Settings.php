<?php
namespace WCP\Admin;

defined( 'ABSPATH' ) || exit;

class Settings {

    const OPT_TOKENS  = 'wcp_tokens';
    const OPT_API_KEY = 'wcp_api_key';
    const SLUG        = 'wcp-settings';

    public static function init(): void {
        add_action( 'admin_menu', [ __CLASS__, 'menu' ] );
        add_action( 'admin_init', [ __CLASS__, 'register' ] );
    }

    public static function menu(): void {
        add_menu_page(
            'WooCommerce CRM',
            'WC CRM',
            'manage_options',
            self::SLUG,
            [ __CLASS__, 'render' ],
            'dashicons-groups',
            56
        );
    }

    public static function register(): void {
        register_setting(
            'wcp_settings',
            self::OPT_TOKENS,
            [
                'type' => 'array',
                'sanitize_callback' => [ __CLASS__, 'sanitize_tokens' ],
                'default' => []
            ]
        );
        add_settings_section( 'wcp_int', __( 'Integrations', 'wcp' ), function () {
            echo '<p>' . esc_html__( 'Store API tokens (leave empty to disable).', 'wcp' ) . '</p>';
        }, self::SLUG );

        foreach ( self::fields() as $k => $label ) {
            add_settings_field(
                $k,
                esc_html( $label ),
                [ __CLASS__, 'field' ],
                self::SLUG,
                'wcp_int',
                [ 'key' => $k ]
            );
        }
    }

    protected static function fields(): array {
        return [
            'hubspot' => 'HubSpot Token',
            // Add additional tokens here.
        ];
    }

    public static function sanitize_tokens( $input ): array {
        $clean = [];
        if ( is_array( $input ) ) {
            foreach ( self::fields() as $k => $_ ) {
                if ( ! empty( $input[ $k ] ) ) {
                    $clean[ $k ] = trim( wp_unslash( $input[ $k ] ) );
                }
            }
        }
        return $clean;
    }

    public static function field( array $args ): void {
        $tokens = get_option( self::OPT_TOKENS, [] );
        $key = $args['key'];
        $val = $tokens[ $key ] ?? '';
        printf(
            '<input type="password" name="%1$s[%2$s]" id="%2$s" value="%3$s" class="regular-text" autocomplete="off" />%4$s',
            esc_attr( self::OPT_TOKENS ),
            esc_attr( $key ),
            esc_attr( $val ),
            $val ? ' <span style="color:green;">' . esc_html__( 'Saved', 'wcp' ) . '</span>' : ''
        );
    }

    protected static function ensure_api_key(): string {
        $key = get_option( self::OPT_API_KEY );
        if ( ! $key ) {
            $key = bin2hex( random_bytes( 16 ) );
            update_option( self::OPT_API_KEY, $key );
        }
        return $key;
    }

    public static function render(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Access denied', 'wcp' ) );
        }
        $api_key = self::ensure_api_key();
        echo '<div class="wrap"><h1>WooCommerce CRM – Settings</h1>';

        echo '<h2>API Key</h2><p><code>' . esc_html( $api_key ) . '</code></p>';
        echo '<p>' . esc_html__( 'Use this key in the X-WCP-Key header for REST lead creation.', 'wcp' ) . '</p>';

        echo '<hr><h2>' . esc_html__( 'Integrations', 'wcp' ) . '</h2>';
        echo '<form method="post" action="options.php">';
        settings_fields( 'wcp_settings' );
        do_settings_sections( self::SLUG );
        submit_button();
        echo '</form></div>';
    }
}
