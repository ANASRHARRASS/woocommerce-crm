<?php

namespace Anas\WCCRM\Admin;

use Anas\WCCRM\Shipping\QuoteService;
use Anas\WCCRM\Shipping\ShippingCarrierRegistry;
use Anas\WCCRM\Shipping\DTO\ShipmentRequest;

defined( 'ABSPATH' ) || exit;

/**
 * Admin shipping rates page for testing and configuration
 */
class ShippingRatesPage {

    private QuoteService $quoteService;
    private ShippingCarrierRegistry $registry;

    public function __construct( QuoteService $quoteService, ShippingCarrierRegistry $registry ) {
        $this->quoteService = $quoteService;
        $this->registry = $registry;
        $this->init_hooks();
    }

    /**
     * Initialize admin hooks
     */
    private function init_hooks(): void {
        add_action( 'admin_post_wccrm_test_shipping_quote', [ $this, 'handle_test_quote' ] );
    }

    /**
     * Render the shipping rates page
     */
    public function render(): void {
        // Check permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'wccrm' ) );
        }

        // Handle notices
        $this->display_notices();
        
        $carriers = $this->registry->get_all();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__( 'WooCommerce CRM - Shipping Rates', 'wccrm' ); ?></h1>
            
            <div class="card">
                <h2><?php echo esc_html__( 'Registered Carriers', 'wccrm' ); ?></h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__( 'Carrier ID', 'wccrm' ); ?></th>
                            <th><?php echo esc_html__( 'Label', 'wccrm' ); ?></th>
                            <th><?php echo esc_html__( 'Status', 'wccrm' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( empty( $carriers ) ): ?>
                            <tr>
                                <td colspan="3"><?php echo esc_html__( 'No carriers registered.', 'wccrm' ); ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ( $carriers as $id => $carrier ): ?>
                                <tr>
                                    <td><code><?php echo esc_html( $id ); ?></code></td>
                                    <td><?php echo esc_html( $carrier->get_label() ); ?></td>
                                    <td>
                                        <span class="status-active"><?php echo esc_html__( 'Active', 'wccrm' ); ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <h2><?php echo esc_html__( 'Test Shipping Quote', 'wccrm' ); ?></h2>
                <p><?php echo esc_html__( 'Use this form to test shipping rate calculations with registered carriers.', 'wccrm' ); ?></p>
                
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <?php wp_nonce_field( 'wccrm_test_shipping_quote', 'wccrm_nonce' ); ?>
                    <input type="hidden" name="action" value="wccrm_test_shipping_quote">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php echo esc_html__( 'Origin Country', 'wccrm' ); ?></th>
                            <td>
                                <input type="text" name="origin_country" value="<?php echo esc_attr( $_POST['origin_country'] ?? 'US' ); ?>" class="regular-text" />
                                <p class="description"><?php echo esc_html__( '2-letter country code (e.g., US, CA, GB)', 'wccrm' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo esc_html__( 'Origin Postcode', 'wccrm' ); ?></th>
                            <td>
                                <input type="text" name="origin_postcode" value="<?php echo esc_attr( $_POST['origin_postcode'] ?? '90210' ); ?>" class="regular-text" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo esc_html__( 'Destination Country', 'wccrm' ); ?></th>
                            <td>
                                <input type="text" name="dest_country" value="<?php echo esc_attr( $_POST['dest_country'] ?? 'US' ); ?>" class="regular-text" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo esc_html__( 'Destination Postcode', 'wccrm' ); ?></th>
                            <td>
                                <input type="text" name="dest_postcode" value="<?php echo esc_attr( $_POST['dest_postcode'] ?? '10001' ); ?>" class="regular-text" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo esc_html__( 'Weight (lbs)', 'wccrm' ); ?></th>
                            <td>
                                <input type="number" step="0.1" name="weight" value="<?php echo esc_attr( $_POST['weight'] ?? '5.0' ); ?>" class="small-text" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo esc_html__( 'Dimensions (inches)', 'wccrm' ); ?></th>
                            <td>
                                <input type="number" step="0.1" name="length" value="<?php echo esc_attr( $_POST['length'] ?? '12' ); ?>" class="small-text" placeholder="Length" />
                                <input type="number" step="0.1" name="width" value="<?php echo esc_attr( $_POST['width'] ?? '8' ); ?>" class="small-text" placeholder="Width" />
                                <input type="number" step="0.1" name="height" value="<?php echo esc_attr( $_POST['height'] ?? '6' ); ?>" class="small-text" placeholder="Height" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo esc_html__( 'Currency', 'wccrm' ); ?></th>
                            <td>
                                <input type="text" name="currency" value="<?php echo esc_attr( $_POST['currency'] ?? 'USD' ); ?>" class="small-text" />
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button( __( 'Get Shipping Quotes', 'wccrm' ), 'primary', 'submit' ); ?>
                </form>
            </div>

            <?php if ( isset( $_GET['wccrm_quotes'] ) && $_GET['wccrm_quotes'] === 'success' ): ?>
                <div class="card">
                    <h2><?php echo esc_html__( 'Quote Results', 'wccrm' ); ?></h2>
                    <?php $this->display_quote_results(); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Handle test quote request
     */
    public function handle_test_quote(): void {
        // Check permissions and nonce
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to perform this action.', 'wccrm' ) );
        }

        check_admin_referer( 'wccrm_test_shipping_quote', 'wccrm_nonce' );

        try {
            $request_data = [
                'origin_country' => sanitize_text_field( $_POST['origin_country'] ?? 'US' ),
                'origin_postcode' => sanitize_text_field( $_POST['origin_postcode'] ?? '' ),
                'dest_country' => sanitize_text_field( $_POST['dest_country'] ?? 'US' ),
                'dest_postcode' => sanitize_text_field( $_POST['dest_postcode'] ?? '' ),
                'weight' => floatval( $_POST['weight'] ?? 1.0 ),
                'length' => floatval( $_POST['length'] ?? 10.0 ),
                'width' => floatval( $_POST['width'] ?? 10.0 ),
                'height' => floatval( $_POST['height'] ?? 10.0 ),
                'currency' => sanitize_text_field( $_POST['currency'] ?? 'USD' ),
            ];

            $request = new ShipmentRequest( $request_data );
            $quotes = $this->quoteService->get_quotes( $request );

            // Store results in transient for display
            set_transient( 'wccrm_test_quotes_' . get_current_user_id(), [
                'request' => $request_data,
                'quotes' => $quotes,
                'timestamp' => time(),
            ], 300 ); // 5 minutes

            $redirect_url = add_query_arg( [
                'page' => 'wccrm-shipping-rates',
                'wccrm_quotes' => 'success',
            ], admin_url( 'admin.php' ) );

        } catch ( \Exception $e ) {
            error_log( 'WCCRM Shipping: Quote test error - ' . $e->getMessage() );
            
            $redirect_url = add_query_arg( [
                'page' => 'wccrm-shipping-rates',
                'wccrm_quotes' => 'error',
                'message' => urlencode( __( 'Quote test failed. Please check error logs.', 'wccrm' ) ),
            ], admin_url( 'admin.php' ) );
        }

        // Preserve form data for retry
        $form_data = http_build_query( $_POST );
        $redirect_url .= '&' . $form_data;

        wp_safe_redirect( $redirect_url );
        exit;
    }

    /**
     * Display quote results
     */
    private function display_quote_results(): void {
        $data = get_transient( 'wccrm_test_quotes_' . get_current_user_id() );
        
        if ( ! $data || ! is_array( $data ) ) {
            echo '<p>' . esc_html__( 'No quote results found.', 'wccrm' ) . '</p>';
            return;
        }

        $request = $data['request'];
        $quotes = $data['quotes'];
        $timestamp = $data['timestamp'];
        ?>
        <p><strong><?php echo esc_html__( 'Request Details:', 'wccrm' ); ?></strong></p>
        <ul>
            <li><?php printf( __( 'Route: %s %s → %s %s', 'wccrm' ), 
                esc_html( $request['origin_country'] ), 
                esc_html( $request['origin_postcode'] ),
                esc_html( $request['dest_country'] ), 
                esc_html( $request['dest_postcode'] ) ); ?></li>
            <li><?php printf( __( 'Weight: %s lbs', 'wccrm' ), esc_html( $request['weight'] ) ); ?></li>
            <li><?php printf( __( 'Dimensions: %s × %s × %s inches', 'wccrm' ), 
                esc_html( $request['length'] ), 
                esc_html( $request['width'] ), 
                esc_html( $request['height'] ) ); ?></li>
            <li><?php printf( __( 'Generated: %s', 'wccrm' ), esc_html( date( 'Y-m-d H:i:s', $timestamp ) ) ); ?></li>
        </ul>

        <?php if ( empty( $quotes ) ): ?>
            <p><?php echo esc_html__( 'No quotes returned by carriers.', 'wccrm' ); ?></p>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__( 'Carrier', 'wccrm' ); ?></th>
                        <th><?php echo esc_html__( 'Service', 'wccrm' ); ?></th>
                        <th><?php echo esc_html__( 'Rate', 'wccrm' ); ?></th>
                        <th><?php echo esc_html__( 'Transit Time', 'wccrm' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $quotes as $quote ): ?>
                        <tr>
                            <td><?php echo esc_html( $quote->carrier_id ); ?></td>
                            <td><?php echo esc_html( $quote->service_name ); ?></td>
                            <td><?php echo esc_html( $quote->get_formatted_amount() ); ?></td>
                            <td><?php echo esc_html( $quote->get_transit_description() ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <?php
    }

    /**
     * Display admin notices
     */
    private function display_notices(): void {
        if ( isset( $_GET['wccrm_quotes'] ) && $_GET['wccrm_quotes'] === 'error' ) {
            $message = isset( $_GET['message'] ) ? urldecode( $_GET['message'] ) : __( 'An error occurred.', 'wccrm' );
            printf(
                '<div class="notice notice-error is-dismissible"><p>%s</p></div>',
                esc_html( $message )
            );
        }
    }
}