<?php

namespace Anas\WCCRM\Shipping\DTO;

defined( 'ABSPATH' ) || exit;

/**
 * Shipping rate data transfer object
 */
class Rate {

    public string $carrier_id;
    public string $service_code;
    public string $service_name;
    public float $amount;
    public string $currency;
    public ?int $transit_days;
    public array $meta;

    public function __construct( array $data = [] ) {
        $this->carrier_id = sanitize_text_field( $data['carrier_id'] ?? '' );
        $this->service_code = sanitize_text_field( $data['service_code'] ?? '' );
        $this->service_name = sanitize_text_field( $data['service_name'] ?? '' );
        $this->amount = floatval( $data['amount'] ?? 0.0 );
        $this->currency = sanitize_text_field( $data['currency'] ?? 'USD' );
        $this->transit_days = isset( $data['transit_days'] ) ? absint( $data['transit_days'] ) : null;
        $this->meta = is_array( $data['meta'] ?? null ) ? $data['meta'] : [];
    }

    /**
     * Convert to array for serialization
     */
    public function to_array(): array {
        return [
            'carrier_id' => $this->carrier_id,
            'service_code' => $this->service_code,
            'service_name' => $this->service_name,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'transit_days' => $this->transit_days,
            'meta' => $this->meta,
        ];
    }

    /**
     * Get formatted amount with currency
     */
    public function get_formatted_amount(): string {
        return number_format( $this->amount, 2 ) . ' ' . $this->currency;
    }

    /**
     * Get transit time description
     */
    public function get_transit_description(): string {
        if ( $this->transit_days === null ) {
            return __( 'Transit time not available', 'wccrm' );
        }

        if ( $this->transit_days === 0 ) {
            return __( 'Same day delivery', 'wccrm' );
        }

        if ( $this->transit_days === 1 ) {
            return __( '1 business day', 'wccrm' );
        }

        return sprintf( __( '%d business days', 'wccrm' ), $this->transit_days );
    }
}