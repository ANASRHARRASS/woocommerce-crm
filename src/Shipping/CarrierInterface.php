<?php

namespace Anas\WCCRM\Shipping;

use Anas\WCCRM\Shipping\DTO\ShipmentRequest;

defined( 'ABSPATH' ) || exit;

/**
 * Interface for shipping carrier implementations
 */
interface CarrierInterface {

    /**
     * Get carrier unique identifier
     */
    public function get_id(): string;

    /**
     * Get carrier display label
     */
    public function get_label(): string;

    /**
     * Get shipping rates for the given request
     * 
     * @param ShipmentRequest $request Shipping request details
     * @return \Anas\WCCRM\Shipping\DTO\Rate[]
     */
    public function quote( ShipmentRequest $request ): array;
}