<?php

namespace Anas\WCCRM\Admin;

use Anas\WCCRM\Orders\BackfillRunner;
use Anas\WCCRM\Contacts\ContactRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Admin tools page for maintenance operations
 */
class ToolsPage {

    private BackfillRunner $backfillRunner;

    public function __construct( ContactRepository $contactRepository ) {
        $this->backfillRunner = new BackfillRunner( $contactRepository );
        $this->init_hooks();
    }

    /**
     * Initialize admin hooks
     */
    private function init_hooks(): void {
        add_action( 'admin_post_wccrm_backfill_orders', [ $this, 'handle_backfill_orders' ] );
        add_action( 'admin_post_wccrm_reset_backfill', [ $this, 'handle_reset_backfill' ] );
    }

    /**
     * Render the tools page
     */
    public function render(): void {
        // Check permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'wccrm' ) );
        }

        // Get backfill progress
        $progress = $this->backfillRunner->get_progress();
        
        // Handle notices
        $this->display_notices();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__( 'WooCommerce CRM - Maintenance Tools', 'wccrm' ); ?></h1>
            
            <div class="card">
                <h2><?php echo esc_html__( 'Order Backfill', 'wccrm' ); ?></h2>
                <p>
                    <?php echo esc_html__( 'Synchronize existing WooCommerce orders with CRM contacts. This process will link orders to contacts and update order statistics.', 'wccrm' ); ?>
                </p>
                <p>
                    <strong><?php echo esc_html__( 'Note:', 'wccrm' ); ?></strong>
                    <?php echo esc_html__( 'This is a non-destructive operation. Existing contacts and data will not be modified unless they are incomplete.', 'wccrm' ); ?>
                </p>
                
                <h3><?php echo esc_html__( 'Progress', 'wccrm' ); ?></h3>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php echo esc_html__( 'Total Orders', 'wccrm' ); ?></th>
                        <td><?php echo esc_html( number_format( $progress['total_orders'] ) ); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__( 'Processed Orders', 'wccrm' ); ?></th>
                        <td><?php echo esc_html( number_format( $progress['processed_orders'] ) ); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__( 'Remaining Orders', 'wccrm' ); ?></th>
                        <td><?php echo esc_html( number_format( $progress['remaining_orders'] ) ); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__( 'Progress', 'wccrm' ); ?></th>
                        <td>
                            <div style="background: #f0f0f0; border-radius: 3px; overflow: hidden; width: 200px; height: 20px;">
                                <div style="background: #0073aa; height: 100%; width: <?php echo esc_attr( $progress['progress_percent'] ); ?>%;"></div>
                            </div>
                            <?php echo esc_html( $progress['progress_percent'] ); ?>%
                        </td>
                    </tr>
                    <?php if ( $progress['last_processed_id'] > 0 ): ?>
                    <tr>
                        <th scope="row"><?php echo esc_html__( 'Last Processed Order ID', 'wccrm' ); ?></th>
                        <td><?php echo esc_html( $progress['last_processed_id'] ); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>

                <h3><?php echo esc_html__( 'Actions', 'wccrm' ); ?></h3>
                <?php if ( $progress['remaining_orders'] > 0 ): ?>
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline-block; margin-right: 10px;">
                        <?php wp_nonce_field( 'wccrm_backfill_orders', 'wccrm_nonce' ); ?>
                        <input type="hidden" name="action" value="wccrm_backfill_orders">
                        <?php submit_button( __( 'Process Next Batch (50 orders)', 'wccrm' ), 'primary', 'submit', false ); ?>
                    </form>
                <?php else: ?>
                    <p><strong><?php echo esc_html__( 'All orders have been processed!', 'wccrm' ); ?></strong></p>
                <?php endif; ?>

                <?php if ( $progress['last_processed_id'] > 0 ): ?>
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline-block;">
                        <?php wp_nonce_field( 'wccrm_reset_backfill', 'wccrm_nonce' ); ?>
                        <input type="hidden" name="action" value="wccrm_reset_backfill">
                        <?php submit_button( __( 'Reset Progress', 'wccrm' ), 'secondary', 'submit', false ); ?>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Handle backfill orders request
     */
    public function handle_backfill_orders(): void {
        // Check permissions and nonce
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to perform this action.', 'wccrm' ) );
        }

        check_admin_referer( 'wccrm_backfill_orders', 'wccrm_nonce' );

        try {
            $results = $this->backfillRunner->run_batch();
            
            $message = sprintf(
                __( 'Backfill completed: %d processed, %d skipped, %d errors', 'wccrm' ),
                $results['processed'],
                $results['skipped'],
                $results['errors']
            );

            $redirect_url = add_query_arg( [
                'page' => 'wccrm-tools',
                'wccrm_backfill' => 'success',
                'message' => urlencode( $message ),
            ], admin_url( 'admin.php' ) );

        } catch ( \Exception $e ) {
            error_log( 'WCCRM Tools: Backfill error - ' . $e->getMessage() );
            
            $redirect_url = add_query_arg( [
                'page' => 'wccrm-tools',
                'wccrm_backfill' => 'error',
                'message' => urlencode( __( 'Backfill failed. Please check error logs.', 'wccrm' ) ),
            ], admin_url( 'admin.php' ) );
        }

        wp_safe_redirect( $redirect_url );
        exit;
    }

    /**
     * Handle reset backfill progress request
     */
    public function handle_reset_backfill(): void {
        // Check permissions and nonce
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to perform this action.', 'wccrm' ) );
        }

        check_admin_referer( 'wccrm_reset_backfill', 'wccrm_nonce' );

        $this->backfillRunner->reset_progress();

        $redirect_url = add_query_arg( [
            'page' => 'wccrm-tools',
            'wccrm_reset' => 'success',
        ], admin_url( 'admin.php' ) );

        wp_safe_redirect( $redirect_url );
        exit;
    }

    /**
     * Display admin notices
     */
    private function display_notices(): void {
        if ( isset( $_GET['wccrm_backfill'] ) ) {
            $type = $_GET['wccrm_backfill'] === 'success' ? 'notice-success' : 'notice-error';
            $message = isset( $_GET['message'] ) ? urldecode( $_GET['message'] ) : '';
            
            if ( $message ) {
                printf(
                    '<div class="notice %s is-dismissible"><p>%s</p></div>',
                    esc_attr( $type ),
                    esc_html( $message )
                );
            }
        }

        if ( isset( $_GET['wccrm_reset'] ) && $_GET['wccrm_reset'] === 'success' ) {
            printf(
                '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                esc_html__( 'Backfill progress has been reset.', 'wccrm' )
            );
        }
    }
}