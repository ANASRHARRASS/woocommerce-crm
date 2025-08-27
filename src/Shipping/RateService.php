<?php

namespace Anas\WCCRM\Shipping;

use Anas\WCCRM\Shipping\DTO\RateQuote;

defined( 'ABSPATH' ) || exit;

/**
 * Service for collecting shipping quotes from multiple carriers
 */
class RateService {

    private CarrierRegistry $carrierRegistry;

    public function __construct( CarrierRegistry $carrierRegistry ) {
        $this->carrierRegistry = $carrierRegistry;
    }

    /**
     * Get quotes from all enabled carriers
     */
    public function get_quotes( array $context ): array {
        $all_quotes = [];
        $enabled_carriers = $this->carrierRegistry->list_enabled();

        if ( empty( $enabled_carriers ) ) {
            return $all_quotes;
        }

        foreach ( $enabled_carriers as $carrier ) {
            try {
                $quotes = $carrier->get_quotes( $context );
                if ( is_array( $quotes ) ) {
                    $all_quotes = array_merge( $all_quotes, $quotes );
                }
            } catch ( \Exception $e ) {
                // Log error but continue with other carriers
                error_log( 'WCCRM Shipping: Error getting quotes from carrier ' . $carrier->get_key() . ': ' . $e->getMessage() );
            }
        }

        // Sort quotes by cost (ascending)
        usort( $all_quotes, function( RateQuote $a, RateQuote $b ) {
            return $a->total_cost <=> $b->total_cost;
        } );

        return $all_quotes;
    }

    /**
     * Get quotes from specific carrier
     */
    public function get_quotes_from_carrier( string $carrier_key, array $context ): array {
        $carrier = $this->carrierRegistry->get( $carrier_key );
        
        if ( ! $carrier || ! $carrier->is_enabled() ) {
            return [];
        }

        try {
            return $carrier->get_quotes( $context );
        } catch ( \Exception $e ) {
            error_log( 'WCCRM Shipping: Error getting quotes from carrier ' . $carrier_key . ': ' . $e->getMessage() );
            return [];
        }
    }

    /**
     * Build shipping context from WooCommerce package
     */
    public function build_context_from_package( array $package ): array {
        $destination = $package['destination'] ?? [];
        $contents = $package['contents'] ?? [];

        // Calculate total weight
        $total_weight = 0;
        foreach ( $contents as $item ) {
            $product = $item['data'] ?? null;
            if ( $product && method_exists( $product, 'get_weight' ) ) {
                $weight = (float) $product->get_weight();
                $quantity = (int) ( $item['quantity'] ?? 1 );
                $total_weight += $weight * $quantity;
            }
        }

        return [
            'destination' => [
                'country' => $destination['country'] ?? '',
                'state' => $destination['state'] ?? '',
                'postcode' => $destination['postcode'] ?? '',
                'city' => $destination['city'] ?? '',
                'address' => $destination['address'] ?? '',
                'address_2' => $destination['address_2'] ?? '',
            ],
            'contents' => $contents,
            'total_weight' => $total_weight,
            'total_value' => array_sum( array_map( function( $item ) {
                $product = $item['data'] ?? null;
                $price = 0;
                if ( $product && method_exists( $product, 'get_price' ) ) {
                    $price = (float) $product->get_price();
                }
                $quantity = (int) ( $item['quantity'] ?? 1 );
                return $price * $quantity;
            }, $contents ) ),
            'currency' => get_woocommerce_currency(),
        ];
    }
}