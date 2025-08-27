<?php

namespace Anas\WCCRM\Shipping;

use Anas\WCCRM\Shipping\Contracts\ShippingCarrierInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Registry for shipping carriers
 */
class CarrierRegistry {

    private array $carriers = [];

    /**
     * Register a shipping carrier
     */
    public function register( string $key, ShippingCarrierInterface $carrier ): void {
        $this->carriers[ sanitize_key( $key ) ] = $carrier;
    }

    /**
     * Get a carrier by key
     */
    public function get( string $key ): ?ShippingCarrierInterface {
        return $this->carriers[ sanitize_key( $key ) ] ?? null;
    }

    /**
     * Get all registered carriers
     */
    public function list(): array {
        return $this->carriers;
    }

    /**
     * Get enabled carriers only
     */
    public function list_enabled(): array {
        return array_filter( $this->carriers, function( ShippingCarrierInterface $carrier ) {
            return $carrier->is_enabled();
        } );
    }

    /**
     * Check if a carrier is registered
     */
    public function has( string $key ): bool {
        return isset( $this->carriers[ sanitize_key( $key ) ] );
    }

    /**
     * Unregister a carrier
     */
    public function unregister( string $key ): void {
        unset( $this->carriers[ sanitize_key( $key ) ] );
    }
}