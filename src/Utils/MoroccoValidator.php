<?php

namespace Anas\WCCRM\Utils;

defined('ABSPATH') || exit;

/**
 * Morocco-specific validation utilities
 */
class MoroccoValidator
{
    /**
     * Validate Moroccan phone numbers
     * Supports: +212, 0212, mobile (06, 07), landline (05)
     */
    public static function validate_moroccan_phone(string $phone): array
    {
        $phone = preg_replace('/[^\d+]/', '', $phone);

        $patterns = [
            // Mobile with country code
            '/^\+212[67]\d{8}$/' => 'mobile_international',
            '/^0212[67]\d{8}$/' => 'mobile_international_alt',

            // Mobile without country code
            '/^0[67]\d{8}$/' => 'mobile_national',

            // Landline with country code
            '/^\+2125\d{8}$/' => 'landline_international',
            '/^02125\d{8}$/' => 'landline_international_alt',

            // Landline without country code
            '/^05\d{8}$/' => 'landline_national',
        ];

        foreach ($patterns as $pattern => $type) {
            if (preg_match($pattern, $phone)) {
                return [
                    'valid' => true,
                    'type' => $type,
                    'formatted' => self::format_moroccan_phone($phone, $type),
                    'normalized' => self::normalize_moroccan_phone($phone)
                ];
            }
        }

        return [
            'valid' => false,
            'error' => 'Invalid Moroccan phone number format'
        ];
    }

    /**
     * Format phone for display
     */
    private static function format_moroccan_phone(string $phone, string $type): string
    {
        if (strpos($type, 'mobile') !== false) {
            if (strpos($type, 'international') !== false) {
                return preg_replace('/(\+212|0212)([67])(\d{2})(\d{2})(\d{2})(\d{2})/', '+212 $2 $3 $4 $5 $6', $phone);
            } else {
                return preg_replace('/0([67])(\d{2})(\d{2})(\d{2})(\d{2})/', '0$1 $2 $3 $4 $5', $phone);
            }
        } else {
            if (strpos($type, 'international') !== false) {
                return preg_replace('/(\+212|0212)5(\d{2})(\d{2})(\d{2})(\d{2})/', '+212 5 $2 $3 $4 $5', $phone);
            } else {
                return preg_replace('/05(\d{2})(\d{2})(\d{2})(\d{2})/', '05 $1 $2 $3 $4', $phone);
            }
        }
    }

    /**
     * Normalize to international format
     */
    private static function normalize_moroccan_phone(string $phone): string
    {
        $phone = preg_replace('/[^\d+]/', '', $phone);
        $matches = []; // Initialize matches array for IDE

        if (preg_match('/^0([567]\d{8})$/', $phone, $matches)) {
            return '+212' . $matches[1];
        }

        if (preg_match('/^0212([567]\d{8})$/', $phone, $matches)) {
            return '+212' . $matches[1];
        }

        if (preg_match('/^\+212([567]\d{8})$/', $phone)) {
            return $phone;
        }

        return $phone;
    }

    /**
     * Get Moroccan cities with regions
     */
    public static function get_moroccan_cities(): array
    {
        return [
            'casablanca' => [
                'name' => 'Casablanca',
                'region' => 'Casablanca-Settat',
                'postal_codes' => ['20000', '20100', '20200', '20300', '20500']
            ],
            'rabat' => [
                'name' => 'Rabat',
                'region' => 'Rabat-Salé-Kénitra',
                'postal_codes' => ['10000', '10100', '10150']
            ],
            'fez' => [
                'name' => 'Fez',
                'region' => 'Fès-Meknès',
                'postal_codes' => ['30000', '30050', '30100']
            ],
            'marrakech' => [
                'name' => 'Marrakech',
                'region' => 'Marrakech-Safi',
                'postal_codes' => ['40000', '40100', '40150']
            ],
            'agadir' => [
                'name' => 'Agadir',
                'region' => 'Souss-Massa',
                'postal_codes' => ['80000', '80100']
            ],
            'tangier' => [
                'name' => 'Tangier',
                'region' => 'Tanger-Tétouan-Al Hoceïma',
                'postal_codes' => ['90000', '90100']
            ],
            'meknes' => [
                'name' => 'Meknès',
                'region' => 'Fès-Meknès',
                'postal_codes' => ['50000', '50100']
            ],
            'sale' => [
                'name' => 'Salé',
                'region' => 'Rabat-Salé-Kénitra',
                'postal_codes' => ['11000', '11100']
            ],
            'temara' => [
                'name' => 'Témara',
                'region' => 'Rabat-Salé-Kénitra',
                'postal_codes' => ['12000', '12100']
            ],
            'oujda' => [
                'name' => 'Oujda',
                'region' => 'Oriental',
                'postal_codes' => ['60000', '60100']
            ],
            'kenitra' => [
                'name' => 'Kénitra',
                'region' => 'Rabat-Salé-Kénitra',
                'postal_codes' => ['14000', '14100']
            ],
            'tetouan' => [
                'name' => 'Tétouan',
                'region' => 'Tanger-Tétouan-Al Hoceïma',
                'postal_codes' => ['93000', '93100']
            ],
            'el_jadida' => [
                'name' => 'El Jadida',
                'region' => 'Casablanca-Settat',
                'postal_codes' => ['24000', '24100']
            ],
            'nador' => [
                'name' => 'Nador',
                'region' => 'Oriental',
                'postal_codes' => ['62000', '62100']
            ],
            'settat' => [
                'name' => 'Settat',
                'region' => 'Casablanca-Settat',
                'postal_codes' => ['26000', '26100']
            ]
        ];
    }

    /**
     * Get Moroccan regions
     */
    public static function get_moroccan_regions(): array
    {
        return [
            'tanger-tetouan-al_hoceima' => 'Tanger-Tétouan-Al Hoceïma',
            'oriental' => 'Oriental',
            'fes-meknes' => 'Fès-Meknès',
            'rabat-sale-kenitra' => 'Rabat-Salé-Kénitra',
            'beni_mellal-khenifra' => 'Béni Mellal-Khénifra',
            'casablanca-settat' => 'Casablanca-Settat',
            'marrakech-safi' => 'Marrakech-Safi',
            'draa-tafilalet' => 'Drâa-Tafilalet',
            'souss-massa' => 'Souss-Massa',
            'guelmim-oued_noun' => 'Guelmim-Oued Noun',
            'laayoune-sakia_el_hamra' => 'Laâyoune-Sakia El Hamra',
            'dakhla-oued_ed-dahab' => 'Dakhla-Oued Ed-Dahab'
        ];
    }

    /**
     * Validate Moroccan postal code
     */
    public static function validate_postal_code(string $postal_code, string $city = ''): bool
    {
        // Moroccan postal codes are 5 digits
        if (!preg_match('/^\d{5}$/', $postal_code)) {
            return false;
        }

        // If city provided, check if postal code matches
        if ($city) {
            $cities = self::get_moroccan_cities();
            $city_key = strtolower(str_replace([' ', '-', 'é', 'è'], ['_', '_', 'e', 'e'], $city));

            if (isset($cities[$city_key])) {
                return in_array($postal_code, $cities[$city_key]['postal_codes']);
            }
        }

        return true; // Valid format, unknown city
    }
}
