<?php
/**
 * Admin Notice Manager for missing API keys and secrets
 * Displays dismissible notices about missing provider credentials
 */

namespace KS_CRM\Admin;

use KS_CRM\Config\Secrets;

defined( 'ABSPATH' ) || exit;

class Notice_Manager {

    private const OPTION_KEY = 'kscrm_dismissed_notices';

    /**
     * Initialize notice manager
     */
    public function init(): void {
        add_action( 'admin_notices', [ $this, 'show_missing_secrets_notice' ] );
        add_action( 'wp_ajax_kscrm_dismiss_notice', [ $this, 'handle_dismiss_notice' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
    }

    /**
     * Show notice for missing secrets
     */
    public function show_missing_secrets_notice(): void {
        // Only show on admin pages and to users who can manage options
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Check if notice is dismissed for current user
        if ( $this->is_notice_dismissed( 'missing_secrets' ) ) {
            return;
        }

        // Get missing secrets from provider registry
        global $wccrm_plugin;
        if ( ! isset( $wccrm_plugin->providerRegistry ) ) {
            return;
        }

        $required_secrets = $wccrm_plugin->providerRegistry->get_required_secrets();
        if ( empty( $required_secrets ) ) {
            return;
        }

        $missing_secrets = Secrets::get_missing( $required_secrets );
        if ( empty( $missing_secrets ) ) {
            return;
        }

        $this->render_missing_secrets_notice( $missing_secrets );
    }

    /**
     * Render missing secrets notice
     *
     * @param array $missing_secrets Array of missing secret names
     */
    private function render_missing_secrets_notice( array $missing_secrets ): void {
        $count = count( $missing_secrets );
        $secrets_list = implode( ', ', $missing_secrets );
        
        ?>
        <div class="notice notice-warning is-dismissible kscrm-admin-notice" data-notice="missing_secrets">
            <p>
                <strong><?php esc_html_e( 'WooCommerce CRM: Missing API Keys', 'woocommerce-crm' ); ?></strong>
            </p>
            <p>
                <?php
                printf(
                    esc_html( _n(
                        'Your CRM setup is missing %d API key: %s',
                        'Your CRM setup is missing %d API keys: %s',
                        $count,
                        'woocommerce-crm'
                    ) ),
                    esc_html( $count ),
                    '<code>' . esc_html( $secrets_list ) . '</code>'
                );
                ?>
            </p>
            <p>
                <?php esc_html_e( 'Some providers may not work until these credentials are configured. You can define them as PHP constants in wp-config.php, environment variables, or via the filter hook.', 'woocommerce-crm' ); ?>
            </p>
            <p>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=kscrm-tools' ) ); ?>" class="button button-primary">
                    <?php esc_html_e( 'Go to CRM Tools', 'woocommerce-crm' ); ?>
                </a>
                <a href="#" class="button button-secondary kscrm-dismiss-notice" data-notice="missing_secrets">
                    <?php esc_html_e( 'Dismiss for now', 'woocommerce-crm' ); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * Handle AJAX dismiss notice request
     */
    public function handle_dismiss_notice(): void {
        check_ajax_referer( 'kscrm_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Insufficient permissions.' ), 403 );
        }

        $notice_type = sanitize_key( $_POST['notice'] ?? '' );
        if ( empty( $notice_type ) ) {
            wp_die( __( 'Invalid notice type.' ), 400 );
        }

        $this->dismiss_notice( $notice_type );

        wp_send_json_success( [
            'message' => __( 'Notice dismissed successfully.', 'woocommerce-crm' ),
        ] );
    }

    /**
     * Dismiss a notice for current user
     *
     * @param string $notice_type Notice type identifier
     */
    private function dismiss_notice( string $notice_type ): void {
        $user_id = get_current_user_id();
        if ( ! $user_id ) {
            return;
        }

        $dismissed_notices = get_user_meta( $user_id, self::OPTION_KEY, true );
        if ( ! is_array( $dismissed_notices ) ) {
            $dismissed_notices = [];
        }

        $dismissed_notices[ $notice_type ] = time();
        update_user_meta( $user_id, self::OPTION_KEY, $dismissed_notices );
    }

    /**
     * Check if a notice is dismissed for current user
     *
     * @param string $notice_type Notice type identifier
     * @return bool True if dismissed
     */
    private function is_notice_dismissed( string $notice_type ): bool {
        $user_id = get_current_user_id();
        if ( ! $user_id ) {
            return false;
        }

        $dismissed_notices = get_user_meta( $user_id, self::OPTION_KEY, true );
        if ( ! is_array( $dismissed_notices ) ) {
            return false;
        }

        // Check if notice was dismissed and is still valid (7 days)
        if ( isset( $dismissed_notices[ $notice_type ] ) ) {
            $dismissed_time = $dismissed_notices[ $notice_type ];
            $expiry_time = $dismissed_time + ( 7 * DAY_IN_SECONDS );
            
            if ( time() < $expiry_time ) {
                return true;
            }
            
            // Remove expired dismissal
            unset( $dismissed_notices[ $notice_type ] );
            update_user_meta( $user_id, self::OPTION_KEY, $dismissed_notices );
        }

        return false;
    }

    /**
     * Enqueue admin scripts
     *
     * @param string $hook_suffix Current admin page
     */
    public function enqueue_scripts( string $hook_suffix ): void {
        // Only enqueue on admin pages where we show notices
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        wp_enqueue_script( 'jquery' );
        
        // Inline script for notice dismissal
        wp_add_inline_script( 'jquery', "
            jQuery(document).ready(function($) {
                $('.kscrm-dismiss-notice').on('click', function(e) {
                    e.preventDefault();
                    
                    var noticeType = $(this).data('notice');
                    var notice = $(this).closest('.kscrm-admin-notice');
                    
                    $.post(ajaxurl, {
                        action: 'kscrm_dismiss_notice',
                        notice: noticeType,
                        nonce: '" . wp_create_nonce( 'kscrm_admin_nonce' ) . "'
                    }, function(response) {
                        if (response.success) {
                            notice.fadeOut();
                        }
                    });
                });
            });
        " );
    }

    /**
     * Show a custom admin notice
     *
     * @param string $message Notice message
     * @param string $type Notice type (success, error, warning, info)
     * @param bool $dismissible Whether notice is dismissible
     */
    public static function show_notice( string $message, string $type = 'info', bool $dismissible = true ): void {
        $dismissible_class = $dismissible ? 'is-dismissible' : '';
        
        add_action( 'admin_notices', function() use ( $message, $type, $dismissible_class ) {
            echo '<div class="notice notice-' . esc_attr( $type ) . ' ' . esc_attr( $dismissible_class ) . '">';
            echo '<p>' . wp_kses_post( $message ) . '</p>';
            echo '</div>';
        } );
    }

    /**
     * Show update success notice
     */
    public static function show_update_success(): void {
        self::show_notice(
            __( 'WooCommerce CRM settings updated successfully.', 'woocommerce-crm' ),
            'success'
        );
    }

    /**
     * Show export success notice
     *
     * @param string $export_type Type of export completed
     * @param int $count Number of items exported
     */
    public static function show_export_success( string $export_type, int $count ): void {
        $message = sprintf(
            __( 'Successfully exported %d %s records.', 'woocommerce-crm' ),
            $count,
            $export_type
        );
        
        self::show_notice( $message, 'success' );
    }

    /**
     * Show cache clear success notice
     *
     * @param string $cache_type Type of cache cleared
     */
    public static function show_cache_clear_success( string $cache_type ): void {
        $message = sprintf(
            __( '%s cache cleared successfully.', 'woocommerce-crm' ),
            ucfirst( $cache_type )
        );
        
        self::show_notice( $message, 'success' );
    }
}