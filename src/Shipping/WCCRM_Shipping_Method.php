<?php

use Anas\WCCRM\Core\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce shipping method integration
 */
class WCCRM_Shipping_Method extends WC_Shipping_Method {

    public function __construct( $instance_id = 0 ) {
        $this->id = 'wccrm_shipping';
        $this->instance_id = absint( $instance_id );
        $this->method_title = __( 'WCCRM Shipping', 'woocommerce-crm' );
        $this->method_description = __( 'Dynamic shipping rates from multiple carriers via WCCRM', 'woocommerce-crm' );
        $this->supports = [
            'shipping-zones',
            'instance-settings',
        ];

        $this->init();
    }

    public function init(): void {
        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option( 'title', $this->method_title );
        $this->enabled = $this->get_option( 'enabled', 'yes' );

        add_action( 'woocommerce_update_options_shipping_' . $this->id, [ $this, 'process_admin_options' ] );
    }

    public function init_form_fields(): void {
        $this->form_fields = [
            'enabled' => [
                'title' => __( 'Enable/Disable', 'woocommerce-crm' ),
                'type' => 'checkbox',
                'label' => __( 'Enable WCCRM Shipping', 'woocommerce-crm' ),
                'default' => 'yes',
            ],
            'title' => [
                'title' => __( 'Method Title', 'woocommerce-crm' ),
                'type' => 'text',
                'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-crm' ),
                'default' => __( 'WCCRM Shipping', 'woocommerce-crm' ),
                'desc_tip' => true,
            ],
            'carriers_enabled' => [
                'title' => __( 'Enabled Carriers', 'woocommerce-crm' ),
                'type' => 'multiselect',
                'description' => __( 'Select which carriers to use for quotes.', 'woocommerce-crm' ),
                'default' => [ 'example' ],
                'options' => $this->get_available_carriers(),
                'desc_tip' => true,
            ],
            'fallback_rate' => [
                'title' => __( 'Fallback Rate', 'woocommerce-crm' ),
                'type' => 'price',
                'description' => __( 'Rate to use when no carriers return quotes.', 'woocommerce-crm' ),
                'default' => '10.00',
                'desc_tip' => true,
            ],
        ];
    }

    public function calculate_shipping( $package = [] ): void {
        if ( ! $this->is_available( $package ) ) {
            return;
        }

        try {
            $plugin = Plugin::instance();
            $rate_service = $plugin->get_rate_service();
            $context = $rate_service->build_context_from_package( $package );

            $quotes = $rate_service->get_quotes( $context );

            if ( empty( $quotes ) ) {
                // No quotes available, use fallback rate
                $this->add_fallback_rate();
                return;
            }

            // Add each quote as a shipping rate
            foreach ( $quotes as $quote ) {
                $this->add_rate( [
                    'id' => $this->id . '_' . $quote->carrier_key . '_' . sanitize_key( $quote->service_name ),
                    'label' => sprintf( '%s - %s', $quote->service_name, $quote->get_eta_text() ),
                    'cost' => $quote->total_cost,
                    'meta_data' => [
                        'carrier_key' => $quote->carrier_key,
                        'service_name' => $quote->service_name,
                        'eta_days' => $quote->eta_days,
                        'quote_meta' => $quote->meta,
                    ],
                ] );
            }

        } catch ( Exception $e ) {
            // Log error and use fallback rate
            error_log( 'WCCRM Shipping: Error calculating rates - ' . $e->getMessage() );
            $this->add_fallback_rate();
        }
    }

    protected function add_fallback_rate(): void {
        $fallback_cost = (float) $this->get_option( 'fallback_rate', 10.00 );
        
        $this->add_rate( [
            'id' => $this->id . '_fallback',
            'label' => __( 'Standard Shipping', 'woocommerce-crm' ),
            'cost' => $fallback_cost,
            'meta_data' => [
                'is_fallback' => true,
            ],
        ] );
    }

    protected function get_available_carriers(): array {
        try {
            $plugin = Plugin::instance();
            $registry = $plugin->get_carrier_registry();
            $carriers = $registry->list();

            $options = [];
            foreach ( $carriers as $key => $carrier ) {
                $options[ $key ] = $carrier->get_name();
            }

            return $options;
        } catch ( Exception $e ) {
            error_log( 'WCCRM Shipping: Error loading carriers - ' . $e->getMessage() );
            return [ 'example' => 'Example Carrier' ];
        }
    }

    public function is_available( $package ): bool {
        if ( 'yes' !== $this->enabled ) {
            return false;
        }

        // Check if any carriers are available
        try {
            $plugin = Plugin::instance();
            $registry = $plugin->get_carrier_registry();
            $enabled_carriers = $registry->list_enabled();

            if ( empty( $enabled_carriers ) ) {
                // Show warning in admin
                if ( is_admin() ) {
                    add_action( 'admin_notices', function() {
                        echo '<div class="notice notice-warning"><p>';
                        echo esc_html__( 'WCCRM Shipping: No carriers are enabled. Please configure shipping carriers.', 'woocommerce-crm' );
                        echo '</p></div>';
                    } );
                }
                
                // Still allow fallback rate
                return true;
            }

            return true;
        } catch ( Exception $e ) {
            error_log( 'WCCRM Shipping: Error checking availability - ' . $e->getMessage() );
            return true; // Allow fallback rate
        }
    }
}