<?php

namespace Anas\WCCRM\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Admin notices helper
 */
class Notices {

    public static function init(): void {
        add_action( 'admin_notices', [ __CLASS__, 'display_notices' ] );
    }

    public static function display_notices(): void {
        $notice_type = $_GET['wccrm_notice'] ?? '';
        $message = $_GET['message'] ?? '';

        if ( ! $notice_type || ! $message ) {
            return;
        }

        $message = urldecode( sanitize_text_field( $message ) );
        $class = $notice_type === 'error' ? 'notice-error' : 'notice-success';

        printf(
            '<div class="notice %s is-dismissible"><p>%s</p></div>',
            esc_attr( $class ),
            esc_html( $message )
        );
    }

    public static function success( string $message ): string {
        return add_query_arg( [
            'wccrm_notice' => 'updated',
            'message' => urlencode( $message )
        ], '' );
    }

    public static function error( string $message ): string {
        return add_query_arg( [
            'wccrm_notice' => 'error', 
            'message' => urlencode( $message )
        ], '' );
    }
}