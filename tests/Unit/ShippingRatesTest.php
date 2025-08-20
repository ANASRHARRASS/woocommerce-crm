<?php

use PHPUnit\Framework\TestCase;
use YourNamespace\Shipping\Rates;

class ShippingRatesTest extends TestCase
{
    protected $shippingRates;

    protected function setUp(): void
    {
        $this->shippingRates = new Rates();
    }

    public function testGetShippingRates()
    {
        $productAttributes = [
            'weight' => 5,
            'dimensions' => [10, 5, 2],
            'destination' => 'US'
        ];

        $rates = $this->shippingRates->getShippingRates($productAttributes);

        $this->assertIsArray($rates);
        $this->assertNotEmpty($rates);
        $this->assertArrayHasKey('standard', $rates);
        $this->assertArrayHasKey('express', $rates);
    }

    public function testCalculateShippingCost()
    {
        $weight = 10; // in kg
        $destination = 'US';
        $cost = $this->shippingRates->calculateShippingCost($weight, $destination);

        $this->assertIsFloat($cost);
        $this->assertGreaterThan(0, $cost);
    }

    public function testInvalidShippingDestination()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->shippingRates->calculateShippingCost(10, 'InvalidDestination');
    }
}