<?php

namespace Anas\WCCRM\Shipping\Carriers;

use Anas\WCCRM\Shipping\CarrierInterface;
use Anas\WCCRM\Shipping\DTO\ShipmentRequest;
use Anas\WCCRM\Shipping\DTO\Rate;

defined( 'ABSPATH' ) || exit;

/**
 * Sample carrier implementation for testing and demonstration
 */
class SampleCarrier implements CarrierInterface {

    /**
     * Get carrier unique identifier
     */
    public function get_id(): string {
        return 'sample';
    }

    /**
     * Get carrier display label
     */
    public function get_label(): string {
        return __( 'Sample Shipping Carrier', 'wccrm' );
    }

    /**
     * Get shipping rates for the given request
     */
    public function quote( ShipmentRequest $request ): array {
        $rates = [];

        // Generate deterministic sample rates based on weight
        $base_rate = max( 5.00, $request->weight * 2.5 );
        
        // Standard service
        $rates[] = new Rate( [
            'carrier_id' => $this->get_id(),
            'service_code' => 'standard',
            'service_name' => __( 'Standard Shipping', 'wccrm' ),
            'amount' => $base_rate,
            'currency' => $request->currency,
            'transit_days' => 5,
            'meta' => [
                'description' => __( 'Standard ground shipping service', 'wccrm' ),
                'type' => 'ground',
            ],
        ] );

        // Express service
        $rates[] = new Rate( [
            'carrier_id' => $this->get_id(),
            'service_code' => 'express',
            'service_name' => __( 'Express Shipping', 'wccrm' ),
            'amount' => $base_rate * 2.2,
            'currency' => $request->currency,
            'transit_days' => 2,
            'meta' => [
                'description' => __( 'Express air shipping service', 'wccrm' ),
                'type' => 'air',
            ],
        ] );

        // Overnight service (only for lighter packages)
        if ( $request->weight <= 10.0 ) {
            $rates[] = new Rate( [
                'carrier_id' => $this->get_id(),
                'service_code' => 'overnight',
                'service_name' => __( 'Overnight Shipping', 'wccrm' ),
                'amount' => $base_rate * 4.5,
                'currency' => $request->currency,
                'transit_days' => 1,
                'meta' => [
                    'description' => __( 'Next business day delivery', 'wccrm' ),
                    'type' => 'priority',
                    'weight_limit' => 10.0,
                ],
            ] );
        }

        return $rates;
    }
}