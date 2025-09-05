<?php

namespace Anas\WCCRM\Shipping\Carriers;

use Anas\WCCRM\Shipping\Contracts\ShippingCarrierInterface;
use Anas\WCCRM\Shipping\DTO\RateQuote;
use Anas\WCCRM\Utils\MoroccoValidator;

defined('ABSPATH') || exit;

/**
 * Morocco shipping carrier with local providers integration
 */
class MoroccoCarrier implements ShippingCarrierInterface
{
    private const MAJOR_CITIES = [
        'casablanca',
        'rabat',
        'fez',
        'marrakech',
        'agadir',
        'tangier',
        'meknes',
        'sale',
        'oujda',
        'kenitra'
    ];

    public function get_quotes(array $context): array
    {
        $quotes = [];
        $destination = $context['destination'] ?? [];
        $weight = (float) ($context['total_weight'] ?? 1);
        $value = (float) ($context['total_value'] ?? 0);

        // Validate Morocco destination
        if (!$this->is_morocco_destination($destination)) {
            return $quotes;
        }

        $city_key = $this->get_city_key($destination['city'] ?? '');
        $is_major_city = in_array($city_key, self::MAJOR_CITIES);

        // CTM (Compagnie de Transport du Maroc) - National postal service
        $quotes[] = $this->get_ctm_quote($weight, $value, $is_major_city, $destination);

        // Amana Express - Popular in major cities
        if ($is_major_city) {
            $quotes[] = $this->get_amana_quote($weight, $value, $destination);
        }

        // DHL Morocco - International and express domestic
        $quotes[] = $this->get_dhl_morocco_quote($weight, $value, $is_major_city, $destination);

        // Local delivery for major cities
        if ($is_major_city) {
            $quotes[] = $this->get_local_delivery_quote($weight, $value, $destination);
        }

        return array_filter($quotes);
    }

    private function get_ctm_quote(float $weight, float $value, bool $is_major_city, array $destination): RateQuote
    {
        // CTM pricing structure
        $base_cost = 25; // MAD

        // Weight-based pricing
        if ($weight <= 1) {
            $weight_cost = 0;
        } elseif ($weight <= 5) {
            $weight_cost = ($weight - 1) * 5;
        } else {
            $weight_cost = 20 + (($weight - 5) * 8);
        }

        // Distance-based pricing
        $distance_cost = $is_major_city ? 0 : 15;

        // Value-based insurance
        $insurance_cost = $value > 1000 ? ($value * 0.005) : 0;

        $total_cost = $base_cost + $weight_cost + $distance_cost + $insurance_cost;

        return new RateQuote([
            'carrier_key' => $this->get_key(),
            'service_name' => 'CTM Standard',
            'total_cost' => $total_cost,
            'currency' => 'MAD',
            'eta_days' => $is_major_city ? 2 : 4,
            'meta' => [
                'provider' => 'CTM',
                'service_type' => 'standard',
                'tracking_available' => true,
                'cash_on_delivery' => true,
                'description' => 'Service postal national CTM'
            ]
        ]);
    }

    private function get_amana_quote(float $weight, float $value, array $destination): RateQuote
    {
        // Amana pricing (more expensive but faster)
        $base_cost = 35; // MAD
        $weight_cost = $weight * 8;
        $insurance_cost = $value > 500 ? ($value * 0.008) : 0;

        $total_cost = $base_cost + $weight_cost + $insurance_cost;

        return new RateQuote([
            'carrier_key' => $this->get_key(),
            'service_name' => 'Amana Express',
            'total_cost' => $total_cost,
            'currency' => 'MAD',
            'eta_days' => 1,
            'meta' => [
                'provider' => 'Amana',
                'service_type' => 'express',
                'tracking_available' => true,
                'cash_on_delivery' => true,
                'description' => 'Livraison express Amana'
            ]
        ]);
    }

    private function get_dhl_morocco_quote(float $weight, float $value, bool $is_major_city, array $destination): RateQuote
    {
        // DHL Morocco pricing (premium service)
        $base_cost = 60; // MAD
        $weight_cost = $weight * 12;
        $zone_cost = $is_major_city ? 0 : 25;
        $insurance_cost = $value > 2000 ? ($value * 0.003) : 0;

        $total_cost = $base_cost + $weight_cost + $zone_cost + $insurance_cost;

        return new RateQuote([
            'carrier_key' => $this->get_key(),
            'service_name' => 'DHL Morocco Express',
            'total_cost' => $total_cost,
            'currency' => 'MAD',
            'eta_days' => $is_major_city ? 1 : 2,
            'meta' => [
                'provider' => 'DHL',
                'service_type' => 'premium',
                'tracking_available' => true,
                'cash_on_delivery' => false,
                'signature_required' => true,
                'description' => 'Service premium DHL Maroc'
            ]
        ]);
    }

    private function get_local_delivery_quote(float $weight, float $value, array $destination): RateQuote
    {
        // Local delivery service for same city
        $base_cost = 20; // MAD
        $weight_cost = $weight > 2 ? (($weight - 2) * 3) : 0;

        $total_cost = $base_cost + $weight_cost;

        return new RateQuote([
            'carrier_key' => $this->get_key(),
            'service_name' => 'Livraison Locale',
            'total_cost' => $total_cost,
            'currency' => 'MAD',
            'eta_days' => 0, // Same day
            'meta' => [
                'provider' => 'Local',
                'service_type' => 'same_day',
                'tracking_available' => false,
                'cash_on_delivery' => true,
                'description' => 'Livraison le jour même en ville'
            ]
        ]);
    }

    private function is_morocco_destination(array $destination): bool
    {
        $country = strtoupper($destination['country'] ?? '');
        return $country === 'MA' || $country === 'MAR' || $country === 'MOROCCO';
    }

    private function get_city_key(string $city): string
    {
        $city = strtolower($city);
        $city = str_replace([' ', '-', 'é', 'è', 'à'], ['_', '_', 'e', 'e', 'a'], $city);

        // Handle common city variations
        $city_mappings = [
            'casablanca' => 'casablanca',
            'casa' => 'casablanca',
            'dar_el_beida' => 'casablanca',
            'rabat' => 'rabat',
            'fes' => 'fez',
            'fez' => 'fez',
            'marrakech' => 'marrakech',
            'marrakesh' => 'marrakech',
            'agadir' => 'agadir',
            'tanger' => 'tangier',
            'tangier' => 'tangier',
            'meknes' => 'meknes',
            'sale' => 'sale',
            'oujda' => 'oujda',
            'kenitra' => 'kenitra'
        ];

        return $city_mappings[$city] ?? $city;
    }

    public function get_key(): string
    {
        return 'morocco_carrier';
    }

    public function get_name(): string
    {
        return 'Morocco Shipping';
    }

    public function is_enabled(): bool
    {
        // Check if Morocco shipping is enabled in settings
        $enabled = get_option('wccrm_morocco_shipping_enabled', 'yes');
        return $enabled === 'yes';
    }

    /**
     * Get required API keys/secrets for advanced features
     */
    public function get_required_secrets(): array
    {
        return [
            'dhl_api_key' => 'DHL Morocco API Key (optional)',
            'amana_api_key' => 'Amana API Key (optional)'
        ];
    }

    /**
     * Calculate accurate shipping for Cash on Delivery
     */
    public function get_cod_rates(array $context): array
    {
        $quotes = $this->get_quotes($context);

        // Add COD fee for applicable services
        foreach ($quotes as $quote) {
            $meta = $quote->meta;
            if ($meta['cash_on_delivery'] ?? false) {
                $cod_fee = $this->calculate_cod_fee($context['total_value'] ?? 0);
                $quote->total_cost += $cod_fee;
                $quote->meta['cod_fee'] = $cod_fee;
                $quote->meta['cod_available'] = true;
            }
        }

        return $quotes;
    }

    private function calculate_cod_fee(float $order_value): float
    {
        // COD fees based on order value
        if ($order_value <= 500) {
            return 10; // MAD
        } elseif ($order_value <= 1000) {
            return 15;
        } elseif ($order_value <= 2000) {
            return 25;
        } else {
            return $order_value * 0.015; // 1.5% for high-value orders
        }
    }
}
