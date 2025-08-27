<?php

namespace Anas\WCCRM\Shipping\Carriers;

use Anas\WCCRM\Shipping\Contracts\ShippingCarrierInterface;
use Anas\WCCRM\Shipping\DTO\RateQuote;

defined( 'ABSPATH' ) || exit;

/**
 * Example shipping carrier for demonstration
 */
class ExampleCarrier implements ShippingCarrierInterface {

    public function get_quotes( array $context ): array {
        // For demonstration, return a static quote
        return [
            new RateQuote( [
                'carrier_key' => $this->get_key(),
                'service_name' => 'Standard Shipping',
                'total_cost' => 9.99,
                'currency' => $context['currency'] ?? 'USD',
                'eta_days' => 5,
                'meta' => [
                    'description' => 'Example static shipping rate',
                    'tracking_available' => true,
                ],
            ] ),
            new RateQuote( [
                'carrier_key' => $this->get_key(),
                'service_name' => 'Express Shipping',
                'total_cost' => 19.99,
                'currency' => $context['currency'] ?? 'USD',
                'eta_days' => 2,
                'meta' => [
                    'description' => 'Example express shipping rate',
                    'tracking_available' => true,
                ],
            ] ),
        ];
    }

    public function get_key(): string {
        return 'example';
    }

    public function get_name(): string {
        return 'Example Carrier';
    }

    public function is_enabled(): bool {
        // Always enabled for demonstration
        return true;
    }
}