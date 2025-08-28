<?php
/**
 * Abstract base class for Shipping Rate providers
 * Ensures normalized response format
 */

namespace KS_CRM\Shipping;

defined( 'ABSPATH' ) || exit;

abstract class Abstract_Shipping_Rate_Provider {

    /**
     * Get shipping quotes for given context
     *
     * @param array $context Shipping context (destination, weight, dimensions, etc.)
     * @return array Normalized array of shipping rates
     */
    abstract public function get_quotes( array $context ): array;

    /**
     * Get provider key/identifier
     *
     * @return string Provider key
     */
    abstract public function get_key(): string;

    /**
     * Get provider display name
     *
     * @return string Provider name
     */
    abstract public function get_name(): string;

    /**
     * Check if provider is enabled/available
     *
     * @return bool True if enabled
     */
    abstract public function is_enabled(): bool;

    /**
     * Get required secrets for this provider
     *
     * @return array Array of required constant names
     */
    public function get_required_secrets(): array {
        return [];
    }

    /**
     * Normalize shipping rate data to standard format
     * Ensures consistent keys: provider, service, cost, currency, eta
     *
     * @param array $raw_data Raw shipping rate data from provider
     * @return array Normalized shipping rate data
     */
    protected function normalize_rate( array $raw_data ): array {
        return [
            'provider' => sanitize_text_field( $raw_data['provider'] ?? $this->get_name() ),
            'service' => sanitize_text_field( $raw_data['service'] ?? $raw_data['service_name'] ?? 'Standard' ),
            'cost' => floatval( $raw_data['cost'] ?? $raw_data['rate'] ?? $raw_data['price'] ?? 0 ),
            'currency' => strtoupper( sanitize_text_field( $raw_data['currency'] ?? 'USD' ) ),
            'eta' => sanitize_text_field( $raw_data['eta'] ?? $raw_data['delivery_time'] ?? '' ),
            'service_code' => sanitize_key( $raw_data['service_code'] ?? $raw_data['code'] ?? '' ),
            'description' => wp_strip_all_tags( $raw_data['description'] ?? '' ),
        ];
    }

    /**
     * Validate shipping context parameters
     *
     * @param array $context Shipping context
     * @param array $required Required parameter names
     * @throws \InvalidArgumentException If required params missing
     */
    protected function validate_context( array $context, array $required = [] ): void {
        foreach ( $required as $param ) {
            if ( ! isset( $context[ $param ] ) || empty( $context[ $param ] ) ) {
                throw new \InvalidArgumentException( "Missing required shipping context: {$param}" );
            }
        }
    }

    /**
     * Apply default shipping context
     *
     * @param array $context Input context
     * @return array Context with defaults applied
     */
    protected function apply_context_defaults( array $context ): array {
        return wp_parse_args( $context, [
            'weight' => 1,
            'weight_unit' => 'kg',
            'dimensions' => [
                'length' => 10,
                'width' => 10,
                'height' => 10,
                'unit' => 'cm'
            ],
            'from' => [
                'country' => 'US',
                'state' => 'CA',
                'city' => 'Los Angeles',
                'postal_code' => '90210'
            ],
            'to' => [
                'country' => 'US',
                'state' => 'NY', 
                'city' => 'New York',
                'postal_code' => '10001'
            ],
            'value' => 100,
            'currency' => 'USD',
        ] );
    }

    /**
     * Format estimated delivery time
     *
     * @param mixed $eta Raw ETA data
     * @return string Formatted ETA string
     */
    protected function format_eta( $eta ): string {
        if ( is_numeric( $eta ) ) {
            $days = intval( $eta );
            return $days === 1 ? '1 day' : "{$days} days";
        }
        
        if ( is_string( $eta ) && ! empty( $eta ) ) {
            return sanitize_text_field( $eta );
        }
        
        return '';
    }

    /**
     * Convert weight to standard unit (kg)
     *
     * @param float $weight Weight value
     * @param string $unit Weight unit (kg, lb, g, oz)
     * @return float Weight in kg
     */
    protected function normalize_weight( float $weight, string $unit = 'kg' ): float {
        switch ( strtolower( $unit ) ) {
            case 'lb':
            case 'lbs':
                return $weight * 0.453592;
            case 'g':
            case 'gram':
            case 'grams':
                return $weight / 1000;
            case 'oz':
            case 'ounce':
            case 'ounces':
                return $weight * 0.0283495;
            case 'kg':
            case 'kilogram':
            case 'kilograms':
            default:
                return $weight;
        }
    }

    /**
     * Convert dimensions to standard unit (cm)
     *
     * @param array $dimensions Dimensions array with length, width, height, unit
     * @return array Normalized dimensions in cm
     */
    protected function normalize_dimensions( array $dimensions ): array {
        $unit = strtolower( $dimensions['unit'] ?? 'cm' );
        $multiplier = 1;

        switch ( $unit ) {
            case 'in':
            case 'inch':
            case 'inches':
                $multiplier = 2.54;
                break;
            case 'm':
            case 'meter':
            case 'meters':
                $multiplier = 100;
                break;
            case 'mm':
            case 'millimeter':
            case 'millimeters':
                $multiplier = 0.1;
                break;
        }

        return [
            'length' => floatval( $dimensions['length'] ?? 0 ) * $multiplier,
            'width' => floatval( $dimensions['width'] ?? 0 ) * $multiplier,
            'height' => floatval( $dimensions['height'] ?? 0 ) * $multiplier,
            'unit' => 'cm',
        ];
    }
}