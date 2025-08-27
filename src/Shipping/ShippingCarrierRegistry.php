<?php

namespace Anas\WCCRM\Shipping;

defined( 'ABSPATH' ) || exit;

/**
 * Registry for shipping carriers
 */
class ShippingCarrierRegistry {

    private array $carriers = [];

    /**
     * Register a shipping carrier
     */
    public function register( string $id, CarrierInterface $carrier ): void {
        $this->carriers[ sanitize_key( $id ) ] = $carrier;
    }

    /**
     * Get a carrier by ID
     */
    public function get( string $id ): ?CarrierInterface {
        return $this->carriers[ sanitize_key( $id ) ] ?? null;
    }

    /**
     * Get all registered carriers
     */
    public function get_all(): array {
        return $this->carriers;
    }

    /**
     * Get carrier IDs
     */
    public function get_ids(): array {
        return array_keys( $this->carriers );
    }

    /**
     * Check if a carrier is registered
     */
    public function has( string $id ): bool {
        return isset( $this->carriers[ sanitize_key( $id ) ] );
    }

    /**
     * Load carriers via filter
     */
    public function load_carriers(): void {
        $carriers = apply_filters( 'wccrm_register_carriers', [] );
        
        if ( is_array( $carriers ) ) {
            foreach ( $carriers as $id => $carrier ) {
                if ( $carrier instanceof CarrierInterface ) {
                    $this->register( $id, $carrier );
                }
            }
        }
    }
}