<?php

namespace Anas\WCCRM\Shipping\DTO;

defined( 'ABSPATH' ) || exit;

/**
 * Shipment request data transfer object
 */
class ShipmentRequest {

    public string $origin_country;
    public string $origin_postcode;
    public string $dest_country;
    public string $dest_postcode;
    public float $weight;
    public float $length;
    public float $width;
    public float $height;
    public string $currency;

    public function __construct( array $data = [] ) {
        $this->origin_country = sanitize_text_field( $data['origin_country'] ?? '' );
        $this->origin_postcode = sanitize_text_field( $data['origin_postcode'] ?? '' );
        $this->dest_country = sanitize_text_field( $data['dest_country'] ?? '' );
        $this->dest_postcode = sanitize_text_field( $data['dest_postcode'] ?? '' );
        $this->weight = floatval( $data['weight'] ?? 0.0 );
        $this->length = floatval( $data['length'] ?? 0.0 );
        $this->width = floatval( $data['width'] ?? 0.0 );
        $this->height = floatval( $data['height'] ?? 0.0 );
        $this->currency = sanitize_text_field( $data['currency'] ?? 'USD' );
    }

    /**
     * Convert to array for serialization
     */
    public function to_array(): array {
        return [
            'origin_country' => $this->origin_country,
            'origin_postcode' => $this->origin_postcode,
            'dest_country' => $this->dest_country,
            'dest_postcode' => $this->dest_postcode,
            'weight' => $this->weight,
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
            'currency' => $this->currency,
        ];
    }

    /**
     * Get dimensional weight (length * width * height)
     */
    public function get_dimensional_weight(): float {
        return $this->length * $this->width * $this->height;
    }

    /**
     * Get a hash for caching purposes
     */
    public function get_hash(): string {
        return md5( serialize( $this->to_array() ) );
    }
}