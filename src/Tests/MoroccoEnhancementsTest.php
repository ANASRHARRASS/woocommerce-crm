<?php

namespace Anas\WCCRM\Tests;

defined('ABSPATH') || exit;

/**
 * Test file to verify Morocco enhancements work properly
 */
class MoroccoEnhancementsTest
{
    public static function run_tests(): array
    {
        $results = [];

        // Test 1: Morocco Validator
        $results['morocco_validator'] = self::test_morocco_validator();

        // Test 2: Product Form Generator
        $results['product_form_generator'] = self::test_product_form_generator();

        // Test 3: Morocco Carrier
        $results['morocco_carrier'] = self::test_morocco_carrier();

        // Test 4: Integration Manager
        $results['integration_manager'] = self::test_integration_manager();

        return $results;
    }

    private static function test_morocco_validator(): array
    {
        if (!class_exists('Anas\\WCCRM\\Utils\\MoroccoValidator')) {
            return ['status' => 'error', 'message' => 'MoroccoValidator class not found'];
        }

        $validator = new \Anas\WCCRM\Utils\MoroccoValidator();

        // Test phone validation
        $test_phones = [
            '+212 6 12 34 56 78',
            '06 12 34 56 78',
            '0612345678',
            '05 22 12 34 56',
            '0522123456'
        ];

        $valid_count = 0;
        foreach ($test_phones as $phone) {
            $result = $validator::validate_moroccan_phone($phone);
            if ($result['valid']) {
                $valid_count++;
            }
        }

        // Test cities
        $cities = $validator::get_moroccan_cities();
        $regions = $validator::get_moroccan_regions();

        return [
            'status' => 'success',
            'message' => "Phone validation: {$valid_count}/5 passed, " . count($cities) . " cities, " . count($regions) . " regions loaded"
        ];
    }

    private static function test_product_form_generator(): array
    {
        if (!class_exists('Anas\\WCCRM\\Forms\\ProductFormFieldGenerator')) {
            return ['status' => 'error', 'message' => 'ProductFormFieldGenerator class not found'];
        }

        $generator = new \Anas\WCCRM\Forms\ProductFormFieldGenerator();

        // Test with a dummy product ID
        $fields = $generator->generate_product_fields(1);

        // Check if contact fields are generated
        $has_contact_fields = isset($fields['contact_info']) && is_array($fields['contact_info']);
        $has_shipping_fields = isset($fields['shipping_fields']) && is_array($fields['shipping_fields']);

        return [
            'status' => $has_contact_fields ? 'success' : 'warning',
            'message' => "Contact fields: " . ($has_contact_fields ? 'Yes' : 'No') .
                ", Shipping fields: " . ($has_shipping_fields ? 'Yes' : 'No')
        ];
    }

    private static function test_morocco_carrier(): array
    {
        if (!class_exists('Anas\\WCCRM\\Shipping\\Carriers\\MoroccoCarrier')) {
            return ['status' => 'error', 'message' => 'MoroccoCarrier class not found'];
        }

        $carrier = new \Anas\WCCRM\Shipping\Carriers\MoroccoCarrier();

        // Test basic carrier properties
        $key = $carrier->get_key();
        $name = $carrier->get_name();
        $enabled = $carrier->is_enabled();

        // Test quote generation with Morocco context
        $test_context = [
            'destination' => [
                'country' => 'MA',
                'city' => 'Casablanca',
                'postcode' => '20000'
            ],
            'total_weight' => 2.5,
            'total_value' => 500
        ];

        $quotes = $carrier->get_quotes($test_context);
        $quote_count = count($quotes);

        return [
            'status' => 'success',
            'message' => "Carrier: {$name} ({$key}), Enabled: " . ($enabled ? 'Yes' : 'No') .
                ", Generated {$quote_count} quotes for Casablanca"
        ];
    }

    private static function test_integration_manager(): array
    {
        if (!class_exists('Anas\\WCCRM\\Integration\\MoroccoEnhancedCRM')) {
            return ['status' => 'error', 'message' => 'MoroccoEnhancedCRM class not found'];
        }

        $manager = \Anas\WCCRM\Integration\MoroccoEnhancedCRM::instance();

        return [
            'status' => 'success',
            'message' => 'Integration manager initialized successfully'
        ];
    }

    public static function display_test_results(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        echo '<div class="wrap">';
        echo '<h1>Morocco Enhancements Test Results</h1>';

        $results = self::run_tests();

        foreach ($results as $test_name => $result) {
            $status = $result['status'];
            $message = $result['message'];
            $class = $status === 'success' ? 'notice-success' : ($status === 'warning' ? 'notice-warning' : 'notice-error');

            echo '<div class="notice ' . esc_attr($class) . '">';
            echo '<p><strong>' . esc_html(ucfirst(str_replace('_', ' ', $test_name))) . ':</strong> ' . esc_html($message) . '</p>';
            echo '</div>';
        }

        echo '<h2>Troubleshooting</h2>';
        echo '<div class="notice notice-info">';
        echo '<p><strong>If tests show errors:</strong></p>';
        echo '<ul>';
        echo '<li>Ensure all files are uploaded correctly</li>';
        echo '<li>Check that WooCommerce is active (some features require it)</li>';
        echo '<li>Verify PHP version is 8.0 or higher</li>';
        echo '<li>Clear any caching plugins</li>';
        echo '</ul>';
        echo '</div>';

        echo '</div>';
    }
}

// Add test page to admin menu (only in debug mode)
if (defined('WP_DEBUG') && WP_DEBUG) {
    add_action('admin_menu', function () {
        add_submenu_page(
            'crm-dashboard',
            'Morocco Tests',
            'Morocco Tests',
            'manage_options',
            'crm-morocco-tests',
            ['Anas\\WCCRM\\Tests\\MoroccoEnhancementsTest', 'display_test_results']
        );
    });
}
