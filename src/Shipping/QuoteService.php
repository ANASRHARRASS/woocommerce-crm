<?php

namespace Anas\WCCRM\Shipping;

use Anas\WCCRM\Shipping\DTO\ShipmentRequest;
use Anas\WCCRM\Shipping\DTO\Rate;

defined( 'ABSPATH' ) || exit;

/**
 * Shipping quote service with caching
 */
class QuoteService {

    private ShippingCarrierRegistry $registry;

    public function __construct( ShippingCarrierRegistry $registry ) {
        $this->registry = $registry;
    }

    /**
     * Get shipping quotes for the given request
     */
    public function get_quotes( ShipmentRequest $request ): array {
        // Apply before filter
        $request = apply_filters( 'wccrm_before_shipping_quote', $request );
        
        $all_rates = [];
        $carriers = $this->registry->get_all();

        foreach ( $carriers as $carrier_id => $carrier ) {
            try {
                // Check cache first
                $cache_key = $this->get_cache_key( $request, $carrier_id );
                $cached_rates = get_transient( $cache_key );

                if ( $cached_rates !== false && is_array( $cached_rates ) ) {
                    $all_rates = array_merge( $all_rates, $cached_rates );
                    continue;
                }

                // Get fresh quotes
                $rates = $carrier->quote( $request );
                
                if ( is_array( $rates ) ) {
                    // Cache the rates
                    $ttl = apply_filters( 'wccrm_shipping_quote_ttl', 1800 ); // 30 minutes default
                    set_transient( $cache_key, $rates, $ttl );
                    
                    $all_rates = array_merge( $all_rates, $rates );
                }

            } catch ( \Exception $e ) {
                error_log( 'WCCRM QuoteService: Error getting quotes from carrier ' . $carrier_id . ': ' . $e->getMessage() );
            }
        }

        // Apply after filter
        $all_rates = apply_filters( 'wccrm_after_shipping_quote', $all_rates, $request );

        return $all_rates;
    }

    /**
     * Get cache key for request and carrier
     */
    private function get_cache_key( ShipmentRequest $request, string $carrier_id ): string {
        $data = array_merge( $request->to_array(), [ 'carrier_id' => $carrier_id ] );
        return 'wccrm_shipping_quote_' . md5( serialize( $data ) );
    }

    /**
     * Clear quotes cache
     */
    public function clear_cache( ?ShipmentRequest $request = null, ?string $carrier_id = null ): void {
        if ( $request && $carrier_id ) {
            // Clear specific cache
            $cache_key = $this->get_cache_key( $request, $carrier_id );
            delete_transient( $cache_key );
        } else {
            // Clear all shipping quote caches
            global $wpdb;
            $wpdb->query( 
                $wpdb->prepare( 
                    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                    '_transient_wccrm_shipping_quote_%'
                )
            );
        }
    }

    /**
     * Build shipment request from WooCommerce order or cart
     */
    public function build_request_from_wc_data( array $data ): ShipmentRequest {
        $defaults = [
            'origin_country' => get_option( 'woocommerce_default_country', 'US' ),
            'origin_postcode' => get_option( 'woocommerce_store_postcode', '' ),
            'dest_country' => $data['country'] ?? 'US',
            'dest_postcode' => $data['postcode'] ?? '',
            'weight' => $data['weight'] ?? 1.0,
            'length' => $data['length'] ?? 10.0,
            'width' => $data['width'] ?? 10.0,
            'height' => $data['height'] ?? 10.0,
            'currency' => get_woocommerce_currency(),
        ];

        return new ShipmentRequest( array_merge( $defaults, $data ) );
    }
}