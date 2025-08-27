<?php

namespace Anas\WCCRM\Shipping\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * Interface for shipping carrier implementations
 */
interface ShippingCarrierInterface {

    /**
     * Get shipping quotes for given context
     * 
     * @param array $context Shipping context (destination, weight, dimensions, etc.)
     * @return \Anas\WCCRM\Shipping\DTO\RateQuote[]
     */
    public function get_quotes( array $context ): array;

    /**
     * Get carrier key/identifier
     */
    public function get_key(): string;

    /**
     * Get carrier display name
     */
    public function get_name(): string;

    /**
     * Check if carrier is enabled/available
     */
    public function is_enabled(): bool;
}