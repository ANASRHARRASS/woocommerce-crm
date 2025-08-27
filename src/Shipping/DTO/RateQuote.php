<?php

namespace Anas\WCCRM\Shipping\DTO;

defined( 'ABSPATH' ) || exit;

/**
 * Rate quote data transfer object
 */
class RateQuote {

    public string $carrier_key;
    public string $service_name;
    public float $total_cost;
    public string $currency;
    public ?int $eta_days;
    public array $meta;

    public function __construct( array $data ) {
        $this->carrier_key = sanitize_key( $data['carrier_key'] ?? '' );
        $this->service_name = sanitize_text_field( $data['service_name'] ?? '' );
        $this->total_cost = (float) ( $data['total_cost'] ?? 0.0 );
        $this->currency = sanitize_text_field( $data['currency'] ?? 'USD' );
        $this->eta_days = isset( $data['eta_days'] ) ? (int) $data['eta_days'] : null;
        $this->meta = is_array( $data['meta'] ?? null ) ? $data['meta'] : [];
    }

    public function to_array(): array {
        return [
            'carrier_key' => $this->carrier_key,
            'service_name' => $this->service_name,
            'total_cost' => $this->total_cost,
            'currency' => $this->currency,
            'eta_days' => $this->eta_days,
            'meta' => $this->meta,
        ];
    }

    public function get_formatted_cost(): string {
        return number_format( $this->total_cost, 2 ) . ' ' . $this->currency;
    }

    public function get_eta_text(): string {
        if ( $this->eta_days === null ) {
            return 'ETA not available';
        }

        if ( $this->eta_days === 0 ) {
            return 'Same day delivery';
        }

        if ( $this->eta_days === 1 ) {
            return '1 business day';
        }

        return $this->eta_days . ' business days';
    }
}