<?php

namespace Anas\WCCRM\Integration;

use Anas\WCCRM\Forms\ProductFormFieldGenerator;
use Anas\WCCRM\Utils\MoroccoValidator;

defined('ABSPATH') || exit;

/**
 * WooCommerce Product Integration Service
 * Handles product-specific forms, attributes, and Morocco-specific requirements
 * 
 * @phpstan-ignore-next-line
 * WooCommerce functions are loaded at runtime by WordPress
 * @see WC() Global WooCommerce instance function
 * @see wc_get_order() WooCommerce order retrieval function
 * @see get_woocommerce_currency() WooCommerce currency function
 * @see woocommerce_wp_checkbox() WooCommerce admin checkbox function
 * @see woocommerce_wp_select() WooCommerce admin select function
 */
class WooCommerceProductIntegration
{
    private ProductFormFieldGenerator $fieldGenerator;

    public function __construct()
    {
        $this->fieldGenerator = new ProductFormFieldGenerator();
        $this->init_hooks();
    }

    /**
     * Initialize WooCommerce integration hooks
     */
    private function init_hooks(): void
    {
        // Product page integration
        add_action('woocommerce_single_product_summary', [$this, 'add_crm_form_to_product'], 25);

        // Cart and checkout integration
        add_action('woocommerce_before_add_to_cart_button', [$this, 'add_product_specific_fields']);
        add_action('woocommerce_add_to_cart', [$this, 'capture_product_data'], 10, 6);

        // Order integration with Morocco-specific data
        add_action('woocommerce_checkout_create_order', [$this, 'add_morocco_order_data']);
        add_action('woocommerce_new_order', [$this, 'sync_order_to_crm'], 20, 1);

        // Admin product settings
        add_filter('woocommerce_product_data_tabs', [$this, 'add_crm_product_tab']);
        add_action('woocommerce_product_data_panels', [$this, 'add_crm_product_panel']);
        add_action('woocommerce_process_product_meta', [$this, 'save_crm_product_settings']);

        // Shipping integration
        add_filter('woocommerce_package_rates', [$this, 'add_morocco_shipping_rates'], 10, 2);
    }

    /**
     * Add CRM form to product pages
     */
    public function add_crm_form_to_product(): void
    {
        global $product;

        if (!$product || !$product->is_purchasable()) {
            return;
        }

        $crm_form_enabled = get_post_meta($product->get_id(), '_wccrm_form_enabled', true);
        if ($crm_form_enabled !== 'yes') {
            return;
        }

        echo '<div class="wccrm-product-form-wrapper">';
        echo '<h3>' . esc_html__('Informations de livraison / Delivery Information', 'woocommerce-crm') . '</h3>';
        echo $this->render_product_crm_form($product->get_id());
        echo '</div>';
    }

    /**
     * Add product-specific fields before add to cart
     */
    public function add_product_specific_fields(): void
    {
        global $product;

        if (!$product) {
            return;
        }

        $quick_capture_enabled = get_post_meta($product->get_id(), '_wccrm_quick_capture', true);
        if ($quick_capture_enabled !== 'yes') {
            return;
        }

        $fields = $this->fieldGenerator->generate_product_fields($product->get_id());

        echo '<div class="wccrm-quick-capture-fields">';
        echo '<h4>' . esc_html__('Informations rapides / Quick Information', 'woocommerce-crm') . '</h4>';

        // Only show essential fields for quick capture
        $essential_fields = ['first_name', 'last_name', 'phone', 'city'];

        foreach ($fields['contact_info'] ?? [] as $field) {
            if (in_array($field['key'], $essential_fields)) {
                echo $this->render_form_field($field);
            }
        }

        echo '</div>';

        // Add JavaScript for field validation
        $this->add_quick_capture_js();
    }

    /**
     * Capture product data when added to cart
     */
    public function capture_product_data($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data): void
    {
        $crm_data = [];

        // Capture quick form data if submitted
        // @phpstan-ignore-next-line - $_POST is a PHP superglobal
        if (!empty($_POST['wccrm_quick_capture'])) {
            // @phpstan-ignore-next-line - $_POST is a PHP superglobal  
            $crm_data = $this->sanitize_quick_capture_data($_POST);

            // Validate Morocco phone if provided
            if (!empty($crm_data['phone'])) {
                $phone_validation = MoroccoValidator::validate_moroccan_phone($crm_data['phone']);
                if ($phone_validation['valid']) {
                    $crm_data['phone_formatted'] = $phone_validation['formatted'];
                    $crm_data['phone_normalized'] = $phone_validation['normalized'];
                }
            }

            // Store in session for checkout
            $wc = $this->get_wc();
            if ($wc && $wc->session) {
                $existing_data = $wc->session->get('wccrm_lead_data', []);
                $existing_data[$product_id] = $crm_data;
                $wc->session->set('wccrm_lead_data', $existing_data);
            } else {
                // Fallback to storing in user meta if session not available
                if (is_user_logged_in()) {
                    $user_id = get_current_user_id();
                    $existing_data = get_user_meta($user_id, 'wccrm_temp_lead_data', true) ?: [];
                    $existing_data[$product_id] = $crm_data;
                    update_user_meta($user_id, 'wccrm_temp_lead_data', $existing_data);
                }
            }
        }
    }

    /**
     * Add Morocco-specific order data
     */
    public function add_morocco_order_data($order): void
    {
        $wc = $this->get_wc();
        $crm_data = [];

        if ($wc && $wc->session) {
            $crm_data = $wc->session->get('wccrm_lead_data', []);
        }

        if (!empty($crm_data)) {
            // Add as order meta
            $order->add_meta_data('_wccrm_lead_data', $crm_data);

            // Validate and standardize address data
            $billing_city = $order->get_billing_city();
            $billing_phone = $order->get_billing_phone();

            if ($billing_phone) {
                $phone_validation = MoroccoValidator::validate_moroccan_phone($billing_phone);
                if ($phone_validation['valid']) {
                    $order->add_meta_data('_wccrm_phone_normalized', $phone_validation['normalized']);
                    $order->add_meta_data('_wccrm_phone_type', $phone_validation['type']);
                }
            }

            // Validate postal code
            $billing_postcode = $order->get_billing_postcode();
            if ($billing_postcode && $billing_city) {
                $postal_valid = MoroccoValidator::validate_postal_code($billing_postcode, $billing_city);
                $order->add_meta_data('_wccrm_postal_code_valid', $postal_valid ? 'yes' : 'no');
            }
        }
    }

    /**
     * Sync order to CRM with enhanced data
     */
    public function sync_order_to_crm($order_id): void
    {
        $order = $this->get_wc_order($order_id);
        if (!$order) {
            return;
        }

        // Prepare enhanced lead data
        $lead_data = [
            'source' => 'woocommerce_order_morocco',
            'email' => $order->get_billing_email(),
            'phone' => $order->get_meta('_wccrm_phone_normalized') ?: $order->get_billing_phone(),
            'first_name' => $order->get_billing_first_name(),
            'last_name' => $order->get_billing_last_name(),
            'city' => $order->get_billing_city(),
            'region' => $this->get_region_for_city($order->get_billing_city()),
            'country' => $order->get_billing_country(),
            'postal_code' => $order->get_billing_postcode(),
            'address' => $order->get_billing_address_1() . ' ' . $order->get_billing_address_2(),
            'order_data' => [
                'order_id' => $order_id,
                'total' => $order->get_total(),
                'currency' => $order->get_currency(),
                'items' => $this->get_order_items_data($order),
                'shipping_method' => $order->get_shipping_method(),
                'payment_method' => $order->get_payment_method()
            ]
        ];

        // Add CRM lead data if available
        $crm_lead_data = $order->get_meta('_wccrm_lead_data');
        if ($crm_lead_data) {
            $lead_data['additional_data'] = $crm_lead_data;
        }

        // Create or update contact in CRM
        $this->create_or_update_crm_contact($lead_data);
    }

    /**
     * Add Morocco shipping rates
     */
    public function add_morocco_shipping_rates($rates, $package): array
    {
        // Check if destination is Morocco
        $destination = $package['destination'] ?? [];
        if (strtoupper($destination['country'] ?? '') !== 'MA') {
            return $rates;
        }

        // Get Morocco-specific shipping rates
        $morocco_carrier = new \Anas\WCCRM\Shipping\Carriers\MoroccoCarrier();

        if (!$morocco_carrier->is_enabled()) {
            return $rates;
        }

        // Build context for shipping calculation
        $context = [
            'destination' => $destination,
            'total_weight' => $this->calculate_package_weight($package),
            'total_value' => $this->calculate_package_value($package),
            'currency' => $this->get_wc_currency()
        ];

        $morocco_quotes = $morocco_carrier->get_quotes($context);

        // Convert quotes to WooCommerce shipping rates
        foreach ($morocco_quotes as $quote) {
            $rate_id = 'wccrm_morocco_' . sanitize_key($quote->service_name);

            $shipping_rate = $this->create_shipping_rate(
                $rate_id,
                $quote->service_name . ' (' . $quote->get_eta_text() . ')',
                $quote->total_cost,
                [],
                'wccrm_morocco'
            );

            if ($shipping_rate) {
                $rates[$rate_id] = $shipping_rate;
            }
        }
        return $rates;
    }

    /**
     * Add CRM tab to product data
     */
    public function add_crm_product_tab($tabs): array
    {
        $tabs['wccrm'] = [
            'label' => __('CRM Settings', 'woocommerce-crm'),
            'target' => 'wccrm_product_data',
            'class' => []
        ];

        return $tabs;
    }

    /**
     * Add CRM product settings panel
     */
    public function add_crm_product_panel(): void
    {
        global $post;
?>
        <div id="wccrm_product_data" class="panel woocommerce_options_panel">
            <div class="options_group">
                <?php
                $this->render_wc_checkbox([
                    'id' => '_wccrm_form_enabled',
                    'label' => __('Enable CRM Form', 'woocommerce-crm'),
                    'description' => __('Show detailed CRM form on product page', 'woocommerce-crm')
                ]);

                $this->render_wc_checkbox([
                    'id' => '_wccrm_quick_capture',
                    'label' => __('Enable Quick Capture', 'woocommerce-crm'),
                    'description' => __('Show quick contact fields before add to cart', 'woocommerce-crm')
                ]);

                $this->render_wc_select([
                    'id' => '_wccrm_shipping_category',
                    'label' => __('Shipping Category', 'woocommerce-crm'),
                    'options' => [
                        'standard' => __('Standard', 'woocommerce-crm'),
                        'fragile' => __('Fragile', 'woocommerce-crm'),
                        'perishable' => __('Perishable', 'woocommerce-crm'),
                        'oversized' => __('Oversized', 'woocommerce-crm')
                    ]
                ]);
                ?>
            </div>
        </div>
    <?php
    }

    /**
     * Save CRM product settings
     */
    public function save_crm_product_settings($post_id): void
    {
        $fields = ['_wccrm_form_enabled', '_wccrm_quick_capture', '_wccrm_shipping_category'];

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            } else {
                delete_post_meta($post_id, $field);
            }
        }
    }

    // Helper methods
    private function render_product_crm_form(int $product_id): string
    {
        $fields = $this->fieldGenerator->generate_product_fields($product_id);
        $form_html = '<form class="wccrm-product-form" method="post">';

        foreach ($fields as $section => $section_fields) {
            if (empty($section_fields)) continue;

            $form_html .= '<div class="wccrm-section wccrm-section-' . esc_attr($section) . '">';

            foreach ($section_fields as $field) {
                $form_html .= $this->render_form_field($field);
            }

            $form_html .= '</div>';
        }

        $form_html .= wp_nonce_field('wccrm_product_form', '_wccrm_nonce', true, false);
        $form_html .= '<input type="hidden" name="wccrm_product_id" value="' . esc_attr($product_id) . '">';
        $form_html .= '<button type="submit" class="wccrm-submit">' . esc_html__('Envoyer / Submit', 'woocommerce-crm') . '</button>';
        $form_html .= '</form>';

        return $form_html;
    }

    private function render_form_field(array $field): string
    {
        $field_html = '<div class="wccrm-field wccrm-field-' . esc_attr($field['type']) . '">';
        $field_html .= '<label for="wccrm_' . esc_attr($field['key']) . '">' . esc_html($field['label']);

        if ($field['required'] ?? false) {
            $field_html .= ' <span class="required">*</span>';
        }

        $field_html .= '</label>';

        switch ($field['type']) {
            case 'select':
                $field_html .= '<select name="wccrm_' . esc_attr($field['key']) . '" id="wccrm_' . esc_attr($field['key']) . '"';
                if ($field['required'] ?? false) $field_html .= ' required';
                $field_html .= '>';
                $field_html .= '<option value="">' . esc_html__('Sélectionner / Select', 'woocommerce-crm') . '</option>';

                foreach ($field['options'] ?? [] as $value => $label) {
                    $field_html .= '<option value="' . esc_attr($value) . '">' . esc_html($label) . '</option>';
                }

                $field_html .= '</select>';
                break;

            case 'textarea':
                $field_html .= '<textarea name="wccrm_' . esc_attr($field['key']) . '" id="wccrm_' . esc_attr($field['key']) . '"';
                if ($field['required'] ?? false) $field_html .= ' required';
                if ($field['placeholder'] ?? '') $field_html .= ' placeholder="' . esc_attr($field['placeholder']) . '"';
                if ($field['rows'] ?? 0) $field_html .= ' rows="' . intval($field['rows']) . '"';
                $field_html .= '></textarea>';
                break;

            default:
                $field_html .= '<input type="' . esc_attr($field['type']) . '" name="wccrm_' . esc_attr($field['key']) . '" id="wccrm_' . esc_attr($field['key']) . '"';
                if ($field['required'] ?? false) $field_html .= ' required';
                if ($field['placeholder'] ?? '') $field_html .= ' placeholder="' . esc_attr($field['placeholder']) . '"';
                if ($field['min'] ?? 0) $field_html .= ' min="' . intval($field['min']) . '"';
                if ($field['max'] ?? 0) $field_html .= ' max="' . intval($field['max']) . '"';
                $field_html .= '>';
                break;
        }

        $field_html .= '</div>';

        return $field_html;
    }

    private function sanitize_quick_capture_data(array $data): array
    {
        return [
            'first_name' => sanitize_text_field($data['wccrm_first_name'] ?? ''),
            'last_name' => sanitize_text_field($data['wccrm_last_name'] ?? ''),
            'phone' => sanitize_text_field($data['wccrm_phone'] ?? ''),
            'city' => sanitize_text_field($data['wccrm_city'] ?? ''),
            'timestamp' => current_time('mysql')
        ];
    }

    private function get_region_for_city(string $city): string
    {
        $cities = MoroccoValidator::get_moroccan_cities();
        $city_key = strtolower(str_replace(' ', '_', $city));

        return $cities[$city_key]['region'] ?? '';
    }

    private function get_order_items_data($order): array
    {
        $items = [];

        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            $items[] = [
                'product_id' => $item->get_product_id(),
                'name' => $item->get_name(),
                'quantity' => $item->get_quantity(),
                'price' => $item->get_total(),
                'weight' => $product ? $product->get_weight() : 0,
                'categories' => $product ? wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'names']) : []
            ];
        }

        return $items;
    }

    private function calculate_package_weight(array $package): float
    {
        $total_weight = 0;

        foreach ($package['contents'] ?? [] as $item) {
            $product = $item['data'] ?? null;
            if ($product && method_exists($product, 'get_weight')) {
                $weight = (float) $product->get_weight();
                $quantity = (int) ($item['quantity'] ?? 1);
                $total_weight += $weight * $quantity;
            }
        }

        return $total_weight;
    }

    private function calculate_package_value(array $package): float
    {
        $total_value = 0;

        foreach ($package['contents'] ?? [] as $item) {
            $product = $item['data'] ?? null;
            if ($product && method_exists($product, 'get_price')) {
                $price = (float) $product->get_price();
                $quantity = (int) ($item['quantity'] ?? 1);
                $total_value += $price * $quantity;
            }
        }

        return $total_value;
    }

    private function create_or_update_crm_contact(array $lead_data): void
    {
        // This would integrate with your existing ContactRepository
        if (class_exists('Anas\\WCCRM\\Contacts\\ContactRepository')) {
            $contact_repo = new \Anas\WCCRM\Contacts\ContactRepository();
            $contact_repo->upsert_by_email_or_phone($lead_data);
        }
    }

    private function add_quick_capture_js(): void
    {
    ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Morocco phone validation
                $('#wccrm_phone').on('blur', function() {
                    var phone = $(this).val();
                    if (phone) {
                        // Basic Morocco phone format validation
                        var moroccoPhonePattern = /^(\+212|0212|0)[5-7]\d{8}$/;
                        if (!moroccoPhonePattern.test(phone.replace(/\s+/g, ''))) {
                            $(this).addClass('wccrm-error');
                            $(this).after('<span class="wccrm-error-msg">Format invalide. Ex: 06 12 34 56 78</span>');
                        } else {
                            $(this).removeClass('wccrm-error');
                            $(this).next('.wccrm-error-msg').remove();
                        }
                    }
                });

                // Mark form as quick capture
                $('.wccrm-quick-capture-fields').closest('form').append('<input type="hidden" name="wccrm_quick_capture" value="1">');
            });
        </script>
        <style>
            .wccrm-quick-capture-fields {
                background: #f9f9f9;
                padding: 15px;
                margin: 15px 0;
                border-radius: 5px;
            }

            .wccrm-field {
                margin-bottom: 10px;
            }

            .wccrm-field label {
                display: block;
                font-weight: bold;
                margin-bottom: 5px;
            }

            .wccrm-field input,
            .wccrm-field select,
            .wccrm-field textarea {
                width: 100%;
                padding: 8px;
                border: 1px solid #ddd;
                border-radius: 3px;
            }

            .wccrm-error {
                border-color: #e74c3c !important;
            }

            .wccrm-error-msg {
                color: #e74c3c;
                font-size: 12px;
                display: block;
                margin-top: 5px;
            }

            .required {
                color: #e74c3c;
            }
        </style>
<?php
    }

    /**
     * Safely get WooCommerce instance
     */
    private function get_wc()
    {
        // @phpstan-ignore-next-line - WC() is loaded by WooCommerce at runtime
        return function_exists('WC') ? \WC() : null;
    }

    /**
     * Safely get WooCommerce order
     */
    private function get_wc_order($order_id)
    {
        // @phpstan-ignore-next-line - wc_get_order() is loaded by WooCommerce at runtime
        return function_exists('wc_get_order') ? \wc_get_order($order_id) : null;
    }

    /**
     * Safely get WooCommerce currency
     */
    private function get_wc_currency(): string
    {
        // @phpstan-ignore-next-line - get_woocommerce_currency() is loaded by WooCommerce at runtime
        return function_exists('get_woocommerce_currency') ? \get_woocommerce_currency() : 'USD';
    }

    /**
     * Safely create WC_Shipping_Rate
     */
    private function create_shipping_rate($rate_id, $label, $cost, $taxes = [], $method_id = '')
    {
        if (class_exists('WC_Shipping_Rate')) {
            // @phpstan-ignore-next-line - WC_Shipping_Rate is loaded by WooCommerce at runtime
            return new \WC_Shipping_Rate($rate_id, $label, $cost, $taxes, $method_id);
        }
        return null;
    }

    /**
     * Safely render WooCommerce admin checkbox
     */
    private function render_wc_checkbox($args): void
    {
        if (function_exists('woocommerce_wp_checkbox')) {
            // @phpstan-ignore-next-line - woocommerce_wp_checkbox() is loaded by WooCommerce at runtime
            \woocommerce_wp_checkbox($args);
        } else {
            // Fallback HTML
            $id = $args['id'] ?? '';
            $label = $args['label'] ?? '';
            $description = $args['description'] ?? '';

            echo '<p class="form-field">';
            echo '<label for="' . esc_attr($id) . '">';
            echo '<input type="checkbox" name="' . esc_attr($id) . '" id="' . esc_attr($id) . '" value="yes">';
            echo ' ' . esc_html($label) . '</label>';
            if ($description) {
                echo '<span class="description">' . esc_html($description) . '</span>';
            }
            echo '</p>';
        }
    }

    /**
     * Safely render WooCommerce admin select
     */
    private function render_wc_select($args): void
    {
        if (function_exists('woocommerce_wp_select')) {
            // @phpstan-ignore-next-line - woocommerce_wp_select() is loaded by WooCommerce at runtime
            \woocommerce_wp_select($args);
        } else {
            // Fallback HTML
            $id = $args['id'] ?? '';
            $label = $args['label'] ?? '';
            $options = $args['options'] ?? [];

            echo '<p class="form-field">';
            echo '<label for="' . esc_attr($id) . '">' . esc_html($label) . '</label>';
            echo '<select name="' . esc_attr($id) . '" id="' . esc_attr($id) . '">';
            foreach ($options as $value => $text) {
                echo '<option value="' . esc_attr($value) . '">' . esc_html($text) . '</option>';
            }
            echo '</select>';
            echo '</p>';
        }
    }
}
