<?php

namespace Anas\WCCRM\Integration;

defined('ABSPATH') || exit;

/**
 * Morocco-Enhanced CRM Integration Manager
 * Initializes and manages all Morocco-specific and WooCommerce enhancements
 */
class MoroccoEnhancedCRM
{
    private static $instance = null;
    private $wc_integration;

    public static function instance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init(): void
    {
        // Check if WooCommerce is active
        if (!$this->is_woocommerce_active()) {
            add_action('admin_notices', [$this, 'woocommerce_missing_notice']);
            return;
        }

        // Initialize components
        $this->init_morocco_features();
        $this->init_woocommerce_integration();
        $this->init_shipping_enhancements();
        $this->init_form_enhancements();

        // Hook into existing plugin
        add_action('wccrm_after_init', [$this, 'integrate_with_existing_crm']);
    }

    private function init_morocco_features(): void
    {
        // Register Morocco validator
        add_filter('wccrm_phone_validators', function ($validators) {
            $validators['morocco'] = 'Anas\\WCCRM\\Utils\\MoroccoValidator::validate_moroccan_phone';
            return $validators;
        });

        // Add Morocco cities and regions to form options
        add_filter('wccrm_form_city_options', [$this, 'add_morocco_cities']);
        add_filter('wccrm_form_region_options', [$this, 'add_morocco_regions']);

        // Enhanced phone validation for existing forms
        add_filter('wccrm_validate_phone', [$this, 'validate_phone_morocco'], 10, 2);
    }

    private function init_woocommerce_integration(): void
    {
        // Only initialize if WooCommerce functions are available
        if (function_exists('WC')) {
            $this->wc_integration = new WooCommerceProductIntegration();
        }

        // Enhanced order sync
        add_action('woocommerce_new_order', [$this, 'enhanced_order_sync'], 25, 1);

        // Product attribute integration
        add_filter('wccrm_product_form_fields', [$this, 'enhance_product_fields'], 10, 2);
    }

    private function init_shipping_enhancements(): void
    {
        // Register Morocco carrier with existing shipping system
        add_action('init', function () {
            if (class_exists('Anas\\WCCRM\\Core\\Plugin')) {
                $plugin = \Anas\WCCRM\Core\Plugin::instance();
                // Check if get_carrier_registry method exists before calling
                if (is_callable([$plugin, 'get_carrier_registry'])) {
                    // Use dynamic method calling to avoid IDE warnings
                    $method = 'get_carrier_registry';
                    $registry = $plugin->$method();
                    if ($registry && method_exists($registry, 'register')) {
                        $registry->register('morocco', new \Anas\WCCRM\Shipping\Carriers\MoroccoCarrier());
                    }
                }
            }
        });

        // Enhanced shipping calculation for Morocco
        add_filter('woocommerce_package_rates', [$this, 'add_morocco_shipping_context'], 5, 2);
    }

    private function init_form_enhancements(): void
    {
        // Enhanced form field generation
        add_filter('wccrm_form_fields', [$this, 'enhance_form_fields'], 10, 3);

        // Add Morocco-specific field types
        add_filter('wccrm_field_types', [$this, 'add_morocco_field_types']);

        // Custom field validation
        add_filter('wccrm_validate_field', [$this, 'validate_morocco_fields'], 10, 3);
    }

    public function integrate_with_existing_crm($core): void
    {
        // Enhance existing lead creation with Morocco data
        if (method_exists($core, 'leads')) {
            $leads_manager = $core->leads();

            // Add Morocco-specific lead enhancement
            add_filter('wccrm_before_create_lead', [$this, 'enhance_lead_data']);
        }
    }

    public function add_morocco_cities($cities): array
    {
        if (class_exists('Anas\\WCCRM\\Utils\\MoroccoValidator')) {
            $morocco_cities = \Anas\WCCRM\Utils\MoroccoValidator::get_moroccan_cities();

            foreach ($morocco_cities as $key => $city_data) {
                $cities['ma_' . $key] = $city_data['name'] . ', ' . $city_data['region'];
            }
        }

        return $cities;
    }

    public function add_morocco_regions($regions): array
    {
        if (class_exists('Anas\\WCCRM\\Utils\\MoroccoValidator')) {
            $morocco_regions = \Anas\WCCRM\Utils\MoroccoValidator::get_moroccan_regions();

            foreach ($morocco_regions as $key => $name) {
                $regions['ma_' . $key] = $name . ' (Morocco)';
            }
        }

        return $regions;
    }

    public function validate_phone_morocco($is_valid, $phone): bool
    {
        if (!$is_valid && class_exists('Anas\\WCCRM\\Utils\\MoroccoValidator')) {
            $validation = \Anas\WCCRM\Utils\MoroccoValidator::validate_moroccan_phone($phone);
            return $validation['valid'] ?? false;
        }

        return $is_valid;
    }

    public function enhanced_order_sync($order_id): void
    {
        if (!function_exists('wc_get_order')) {
            return;
        }

        // @phpstan-ignore-next-line - wc_get_order() is loaded by WooCommerce at runtime
        $order = \wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Only enhance Morocco orders
        if (strtoupper($order->get_billing_country()) !== 'MA') {
            return;
        }

        $enhanced_data = [
            'source' => 'woocommerce_morocco',
            'email' => $order->get_billing_email(),
            'phone' => $order->get_billing_phone(),
            'first_name' => $order->get_billing_first_name(),
            'last_name' => $order->get_billing_last_name(),
            'country' => 'MA',
            'city' => $order->get_billing_city(),
            'address' => trim($order->get_billing_address_1() . ' ' . $order->get_billing_address_2()),
            'postal_code' => $order->get_billing_postcode(),
            'morocco_data' => [
                'region' => $this->guess_region_from_city($order->get_billing_city()),
                'phone_validated' => false,
                'postal_code_validated' => false
            ]
        ];

        // Validate Morocco-specific data
        if (class_exists('Anas\\WCCRM\\Utils\\MoroccoValidator')) {
            $phone_validation = \Anas\WCCRM\Utils\MoroccoValidator::validate_moroccan_phone($enhanced_data['phone']);
            if ($phone_validation['valid']) {
                $enhanced_data['phone'] = $phone_validation['normalized'];
                $enhanced_data['morocco_data']['phone_validated'] = true;
                $enhanced_data['morocco_data']['phone_type'] = $phone_validation['type'];
            }

            $postal_validation = \Anas\WCCRM\Utils\MoroccoValidator::validate_postal_code(
                $enhanced_data['postal_code'],
                $enhanced_data['city']
            );
            $enhanced_data['morocco_data']['postal_code_validated'] = $postal_validation;
        }

        // Create enhanced lead
        if (class_exists('WCP\\Core') && function_exists('wccrm_get_core')) {
            // @phpstan-ignore-next-line - wccrm_get_core() is loaded by the CRM plugin at runtime
            $core = \wccrm_get_core();
            if ($core && method_exists($core, 'leads')) {
                $core->leads()->create_lead($enhanced_data);
            }
        }
    }

    public function enhance_product_fields($fields, $product_id): array
    {
        if (class_exists('Anas\\WCCRM\\Forms\\ProductFormFieldGenerator')) {
            $generator = new \Anas\WCCRM\Forms\ProductFormFieldGenerator();
            $enhanced_fields = $generator->generate_product_fields($product_id);

            // Merge with existing fields
            if (is_array($enhanced_fields)) {
                foreach ($enhanced_fields as $section => $section_fields) {
                    if (is_array($section_fields)) {
                        $fields = array_merge($fields, $section_fields);
                    }
                }
            }
        }

        return $fields;
    }

    public function add_morocco_shipping_context($rates, $package): array
    {
        $destination = $package['destination'] ?? [];

        // Only enhance for Morocco destinations
        if (strtoupper($destination['country'] ?? '') !== 'MA') {
            return $rates;
        }

        // Add Morocco context to package for carriers
        $package['morocco_context'] = [
            'is_major_city' => $this->is_major_city($destination['city'] ?? ''),
            'region' => $this->guess_region_from_city($destination['city'] ?? ''),
            'postal_code_valid' => $this->is_valid_morocco_postal($destination['postcode'] ?? '', $destination['city'] ?? '')
        ];

        return $rates;
    }

    public function enhance_form_fields($fields, $form_key, $context): array
    {
        // Add Morocco-specific enhancements based on context
        if (isset($context['country']) && strtoupper($context['country']) === 'MA') {
            // Add Morocco-specific phone field
            $fields['phone_morocco'] = [
                'key' => 'phone',
                'type' => 'tel',
                'label' => 'Téléphone / Phone',
                'placeholder' => '+212 6 XX XX XX XX',
                'validation' => 'morocco_phone',
                'required' => true
            ];

            // Add Morocco city selector
            $fields['city_morocco'] = [
                'key' => 'city',
                'type' => 'select',
                'label' => 'Ville / City',
                'options' => $this->get_morocco_city_options(),
                'searchable' => true,
                'required' => true
            ];
        }

        return $fields;
    }

    public function add_morocco_field_types($types): array
    {
        $types['morocco_phone'] = [
            'label' => 'Morocco Phone',
            'description' => 'Moroccan phone number with validation',
            'validation_rules' => ['morocco_phone_format']
        ];

        $types['morocco_city'] = [
            'label' => 'Morocco City',
            'description' => 'Moroccan city selector with regions',
            'options_callback' => [$this, 'get_morocco_city_options']
        ];

        return $types;
    }

    public function validate_morocco_fields($is_valid, $field, $value): bool
    {
        if (!$is_valid) {
            return false;
        }

        switch ($field['type'] ?? '') {
            case 'morocco_phone':
                if (class_exists('Anas\\WCCRM\\Utils\\MoroccoValidator')) {
                    $validation = \Anas\WCCRM\Utils\MoroccoValidator::validate_moroccan_phone($value);
                    return $validation['valid'] ?? false;
                }
                break;

            case 'morocco_city':
                return $this->is_valid_morocco_city($value);
        }

        return $is_valid;
    }

    public function enhance_lead_data($lead_data): array
    {
        // Enhance lead data with Morocco-specific information
        if (isset($lead_data['country']) && strtoupper($lead_data['country']) === 'MA') {
            $lead_data['morocco_enhanced'] = true;

            // Add region if city is provided
            if (!empty($lead_data['city'])) {
                $lead_data['region'] = $this->guess_region_from_city($lead_data['city']);
            }

            // Validate and normalize phone
            if (!empty($lead_data['phone']) && class_exists('Anas\\WCCRM\\Utils\\MoroccoValidator')) {
                $phone_validation = \Anas\WCCRM\Utils\MoroccoValidator::validate_moroccan_phone($lead_data['phone']);
                if ($phone_validation['valid']) {
                    $lead_data['phone_normalized'] = $phone_validation['normalized'];
                    $lead_data['phone_type'] = $phone_validation['type'];
                }
            }
        }

        return $lead_data;
    }

    // Helper methods
    private function is_woocommerce_active(): bool
    {
        return class_exists('WooCommerce') || function_exists('WC');
    }

    public function woocommerce_missing_notice(): void
    {
        echo '<div class="notice notice-warning"><p>';
        echo esc_html__('WooCommerce CRM Morocco Enhanced features require WooCommerce to be installed and activated.', 'woocommerce-crm');
        echo '</p></div>';
    }

    private function is_major_city(string $city): bool
    {
        $major_cities = ['casablanca', 'rabat', 'fez', 'marrakech', 'agadir', 'tangier', 'meknes', 'sale', 'oujda', 'kenitra'];
        $city_normalized = strtolower(str_replace([' ', '-'], ['_', '_'], $city));

        return in_array($city_normalized, $major_cities);
    }

    private function guess_region_from_city(string $city): string
    {
        if (class_exists('Anas\\WCCRM\\Utils\\MoroccoValidator')) {
            $cities = \Anas\WCCRM\Utils\MoroccoValidator::get_moroccan_cities();
            $city_key = strtolower(str_replace([' ', '-'], ['_', '_'], $city));

            return $cities[$city_key]['region'] ?? '';
        }

        return '';
    }

    private function is_valid_morocco_postal(string $postal_code, string $city): bool
    {
        if (class_exists('Anas\\WCCRM\\Utils\\MoroccoValidator')) {
            return \Anas\WCCRM\Utils\MoroccoValidator::validate_postal_code($postal_code, $city);
        }

        return true;
    }

    private function get_morocco_city_options(): array
    {
        if (class_exists('Anas\\WCCRM\\Utils\\MoroccoValidator')) {
            $cities = \Anas\WCCRM\Utils\MoroccoValidator::get_moroccan_cities();
            $options = [];

            foreach ($cities as $key => $city_data) {
                $options[$key] = $city_data['name'] . ' (' . $city_data['region'] . ')';
            }

            return $options;
        }

        return [];
    }

    private function is_valid_morocco_city(string $city): bool
    {
        $options = $this->get_morocco_city_options();
        return array_key_exists($city, $options);
    }
}

// Initialize the Morocco Enhanced CRM
add_action('plugins_loaded', function () {
    if (class_exists('Anas\\WCCRM\\Integration\\MoroccoEnhancedCRM')) {
        \Anas\WCCRM\Integration\MoroccoEnhancedCRM::instance()->init();
    }
}, 20);
