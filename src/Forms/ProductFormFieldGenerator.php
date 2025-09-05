<?php

namespace Anas\WCCRM\Forms;

use Anas\WCCRM\Utils\MoroccoValidator;

defined('ABSPATH') || exit;

/**
 * Enhanced form field generator for product-specific fields
 */
class ProductFormFieldGenerator
{
    /**
     * Generate form fields based on product attributes and Morocco context
     */
    public function generate_product_fields(int $product_id): array
    {
        // Ensure WooCommerce is available
        if (!$this->is_woocommerce_available()) {
            return [];
        }

        $product = $this->get_product($product_id);
        if (!$product) {
            return [];
        }

        $fields = [];

        // Core contact fields with Morocco validation
        $fields['contact_info'] = $this->get_contact_fields();

        // Product-specific fields
        $fields['product_fields'] = $this->get_product_attribute_fields($product);

        // Shipping fields for Morocco
        $fields['shipping_fields'] = $this->get_morocco_shipping_fields();

        // Category-specific fields
        $fields['category_fields'] = $this->get_category_specific_fields($product);

        return $fields;
    }

    /**
     * Core contact fields with Morocco validation
     */
    private function get_contact_fields(): array
    {
        return [
            [
                'key' => 'first_name',
                'type' => 'text',
                'label' => 'Prénom / First Name',
                'required' => true,
                'validation' => 'required|min:2'
            ],
            [
                'key' => 'last_name',
                'type' => 'text',
                'label' => 'Nom / Last Name',
                'required' => true,
                'validation' => 'required|min:2'
            ],
            [
                'key' => 'email',
                'type' => 'email',
                'label' => 'Email',
                'required' => true,
                'validation' => 'required|email'
            ],
            [
                'key' => 'phone',
                'type' => 'tel',
                'label' => 'Téléphone / Phone',
                'required' => true,
                'placeholder' => '06 12 34 56 78 ou +212 6 12 34 56 78',
                'validation' => 'required|morocco_phone',
                'custom_validation' => 'MoroccoValidator::validate_moroccan_phone'
            ]
        ];
    }

    /**
     * Generate fields based on product attributes
     */
    private function get_product_attribute_fields($product): array
    {
        $fields = [];

        if (!method_exists($product, 'get_attributes')) {
            return $fields;
        }

        $attributes = $product->get_attributes();

        foreach ($attributes as $attribute_name => $attribute) {
            $field = $this->convert_attribute_to_field($attribute_name, $attribute, $product);
            if ($field) {
                $fields[] = $field;
            }
        }

        // Add quantity field
        $fields[] = [
            'key' => 'quantity',
            'type' => 'number',
            'label' => 'Quantité / Quantity',
            'required' => true,
            'min' => 1,
            'max' => $product->get_stock_quantity() ?: 999,
            'default' => 1
        ];

        return $fields;
    }

    /**
     * Convert WooCommerce attribute to form field
     */
    private function convert_attribute_to_field(string $name, $attribute, $product): ?array
    {
        $clean_name = sanitize_key($name);
        $label = $this->get_attribute_label($name);

        // Handle different attribute types
        if ($attribute->is_taxonomy()) {
            $terms = wp_get_post_terms($product->get_id(), $attribute->get_name());
            if ($terms && !is_wp_error($terms)) {
                $options = [];
                foreach ($terms as $term) {
                    $options[$term->slug] = $term->name;
                }

                return [
                    'key' => $clean_name,
                    'type' => count($options) > 4 ? 'select' : 'radio',
                    'label' => $label,
                    'options' => $options,
                    'required' => false
                ];
            }
        } else {
            // Non-taxonomy attribute
            $values = $attribute->get_options();
            if ($values) {
                $options = [];
                foreach ($values as $value) {
                    $options[sanitize_key($value)] = $value;
                }

                return [
                    'key' => $clean_name,
                    'type' => count($options) > 1 ? 'select' : 'text',
                    'label' => $label,
                    'options' => count($options) > 1 ? $options : null,
                    'required' => false
                ];
            }
        }

        return null;
    }

    /**
     * Morocco-specific shipping fields
     */
    private function get_morocco_shipping_fields(): array
    {
        $regions = MoroccoValidator::get_moroccan_regions();
        $cities = MoroccoValidator::get_moroccan_cities();

        $city_options = [];
        foreach ($cities as $key => $city_data) {
            $city_options[$key] = $city_data['name'] . ' (' . $city_data['region'] . ')';
        }

        return [
            [
                'key' => 'country',
                'type' => 'hidden',
                'default' => 'MA'
            ],
            [
                'key' => 'region',
                'type' => 'select',
                'label' => 'Région / Region',
                'options' => $regions,
                'required' => true
            ],
            [
                'key' => 'city',
                'type' => 'select',
                'label' => 'Ville / City',
                'options' => $city_options,
                'required' => true,
                'searchable' => true
            ],
            [
                'key' => 'postal_code',
                'type' => 'text',
                'label' => 'Code Postal / Postal Code',
                'required' => true,
                'pattern' => '[0-9]{5}',
                'placeholder' => '20000',
                'validation' => 'required|numeric|digits:5'
            ],
            [
                'key' => 'address_1',
                'type' => 'textarea',
                'label' => 'Adresse / Address',
                'required' => true,
                'rows' => 3
            ],
            [
                'key' => 'address_2',
                'type' => 'text',
                'label' => 'Complément d\'adresse / Address Line 2',
                'required' => false
            ]
        ];
    }

    /**
     * Category-specific fields
     */
    private function get_category_specific_fields($product): array
    {
        $fields = [];

        if (!method_exists($product, 'get_id')) {
            return $fields;
        }

        $categories = wp_get_post_terms($product->get_id(), 'product_cat');

        if (!$categories || is_wp_error($categories)) {
            return $fields;
        }

        foreach ($categories as $category) {
            $category_fields = $this->get_fields_for_category($category);
            $fields = array_merge($fields, $category_fields);
        }

        return $fields;
    }

    /**
     * Get specific fields for product categories
     */
    private function get_fields_for_category(\WP_Term $category): array
    {
        $category_slug = $category->slug;

        switch ($category_slug) {
            case 'electronics':
            case 'electronique':
                return [
                    [
                        'key' => 'warranty_needed',
                        'type' => 'checkbox',
                        'label' => 'Garantie étendue souhaitée / Extended warranty needed',
                        'required' => false
                    ],
                    [
                        'key' => 'installation_service',
                        'type' => 'checkbox',
                        'label' => 'Service d\'installation / Installation service',
                        'required' => false
                    ]
                ];

            case 'clothing':
            case 'vetements':
                return [
                    [
                        'key' => 'size',
                        'type' => 'select',
                        'label' => 'Taille / Size',
                        'options' => [
                            'xs' => 'XS',
                            's' => 'S',
                            'm' => 'M',
                            'l' => 'L',
                            'xl' => 'XL',
                            'xxl' => 'XXL'
                        ],
                        'required' => true
                    ],
                    [
                        'key' => 'color_preference',
                        'type' => 'text',
                        'label' => 'Préférence de couleur / Color preference',
                        'required' => false
                    ]
                ];

            case 'food':
            case 'alimentation':
                return [
                    [
                        'key' => 'delivery_temperature',
                        'type' => 'select',
                        'label' => 'Température de livraison / Delivery temperature',
                        'options' => [
                            'ambient' => 'Température ambiante / Ambient',
                            'refrigerated' => 'Réfrigéré / Refrigerated',
                            'frozen' => 'Congelé / Frozen'
                        ],
                        'required' => true
                    ],
                    [
                        'key' => 'dietary_restrictions',
                        'type' => 'checkbox_group',
                        'label' => 'Restrictions alimentaires / Dietary restrictions',
                        'options' => [
                            'halal' => 'Halal',
                            'vegetarian' => 'Végétarien / Vegetarian',
                            'vegan' => 'Végétalien / Vegan',
                            'gluten_free' => 'Sans gluten / Gluten-free'
                        ],
                        'required' => false
                    ]
                ];

            default:
                return [];
        }
    }

    /**
     * Generate special notes field based on product
     */
    public function get_special_notes_field($product): array
    {
        $placeholder = 'Instructions spéciales, préférences de livraison, etc.';

        if (method_exists($product, 'is_virtual') && $product->is_virtual()) {
            $placeholder = 'Instructions spéciales pour la livraison numérique.';
        } elseif (method_exists($product, 'get_weight') && $product->get_weight()) {
            $placeholder = 'Instructions pour la livraison (étage, code d\'accès, etc.)';
        }

        return [
            'key' => 'special_notes',
            'type' => 'textarea',
            'label' => 'Notes spéciales / Special notes',
            'placeholder' => $placeholder,
            'required' => false,
            'rows' => 4
        ];
    }

    /**
     * Check if WooCommerce is available and functions are loaded
     */
    private function is_woocommerce_available(): bool
    {
        return class_exists('WooCommerce') && function_exists('wc_get_product');
    }

    /**
     * Safely get a WooCommerce product
     * @suppress P1010 - WooCommerce function loaded at runtime
     */
    private function get_product(int $product_id)
    {
        if (!function_exists('wc_get_product')) {
            return null;
        }

        // @phpstan-ignore-next-line - WooCommerce function
        return \wc_get_product($product_id);
    }

    /**
     * Safely get WooCommerce attribute label
     * @suppress P1010 - WooCommerce function loaded at runtime
     */
    private function get_attribute_label(string $name): string
    {
        if (function_exists('wc_attribute_label')) {
            // @phpstan-ignore-next-line - WooCommerce function
            return \wc_attribute_label($name);
        }

        // Fallback: format the name nicely
        return ucfirst(str_replace(['_', '-'], ' ', $name));
    }

    /**
     * Generate complete product form with all WooCommerce data
     * PREMIUM FEATURE: Fetches all product data like premium plugins
     */
    public function generate_complete_woocommerce_form(int $product_id, array $options = []): array
    {
        $product = $this->get_product($product_id);
        if (!$product) {
            return [];
        }

        $form = [
            'product_info' => $this->get_product_info_fields($product),
            'contact_fields' => $this->get_contact_fields(),
            'product_options' => $this->get_product_option_fields($product),
            'shipping_fields' => $this->get_morocco_shipping_fields(),
            'payment_fields' => $this->get_payment_preference_fields(),
            'custom_fields' => $this->get_custom_product_fields($product)
        ];

        // Apply options filtering
        if (!empty($options['sections'])) {
            $form = array_intersect_key($form, array_flip($options['sections']));
        }

        return $form;
    }

    /**
     * Get product information fields - like premium plugins
     */
    private function get_product_info_fields($product): array
    {
        return [
            [
                'key' => 'product_id',
                'type' => 'hidden',
                'value' => $product->get_id()
            ],
            [
                'key' => 'product_name',
                'type' => 'text',
                'label' => 'Product',
                'value' => $product->get_name(),
                'readonly' => true
            ],
            [
                'key' => 'product_price',
                'type' => 'text',
                'label' => 'Price',
                'value' => $product->get_price_html(),
                'readonly' => true
            ],
            [
                'key' => 'product_sku',
                'type' => 'text',
                'label' => 'SKU',
                'value' => $product->get_sku() ?: 'N/A',
                'readonly' => true
            ]
        ];
    }

    /**
     * Get product options (variations, attributes) - like premium plugins
     */
    private function get_product_option_fields($product): array
    {
        $fields = [];

        // Handle variable products
        if ($product->is_type('variable')) {
            $fields[] = [
                'key' => 'product_variation',
                'type' => 'select',
                'label' => 'Choose Option',
                'options' => $this->get_product_variations($product),
                'required' => true
            ];
        }

        // Handle product add-ons/options
        $fields = array_merge($fields, $this->get_product_addons($product));

        return $fields;
    }

    /**
     * Get product variations for variable products
     */
    private function get_product_variations($product): array
    {
        $variations = [];

        if (method_exists($product, 'get_available_variations')) {
            $available_variations = $product->get_available_variations();

            foreach ($available_variations as $variation) {
                $variation_id = $variation['variation_id'];
                $attributes = $variation['attributes'];

                $variation_name = implode(', ', array_map(function ($key, $value) {
                    return ucfirst(str_replace('attribute_', '', $key)) . ': ' . $value;
                }, array_keys($attributes), $attributes));

                $variations[$variation_id] = $variation_name . ' - ' . $variation['price_html'];
            }
        }

        return $variations;
    }

    /**
     * Get product add-ons and custom options
     */
    private function get_product_addons($product): array
    {
        $addons = [];

        // Check for common add-on plugins
        $meta_data = $product->get_meta_data();

        foreach ($meta_data as $meta) {
            $key = $meta->get_data()['key'];
            $value = $meta->get_data()['value'];

            // Handle Product Add-Ons plugin
            if (strpos($key, '_product_addons') !== false && is_array($value)) {
                foreach ($value as $addon) {
                    if (isset($addon['name']) && isset($addon['type'])) {
                        $addons[] = [
                            'key' => 'addon_' . sanitize_key($addon['name']),
                            'type' => $this->convert_addon_type($addon['type']),
                            'label' => $addon['name'],
                            'options' => $addon['options'] ?? [],
                            'required' => $addon['required'] ?? false
                        ];
                    }
                }
            }
        }

        return $addons;
    }

    /**
     * Convert add-on types to form field types
     */
    private function convert_addon_type(string $addon_type): string
    {
        $type_map = [
            'select' => 'select',
            'checkbox' => 'checkbox',
            'radio' => 'radio',
            'text' => 'text',
            'textarea' => 'textarea',
            'file_upload' => 'file',
            'date' => 'date',
            'heading' => 'heading'
        ];

        return $type_map[$addon_type] ?? 'text';
    }

    /**
     * Get payment preference fields
     */
    private function get_payment_preference_fields(): array
    {
        return [
            [
                'key' => 'payment_method',
                'type' => 'radio',
                'label' => 'Payment Method Preference',
                'options' => [
                    'cod' => 'Cash on Delivery (COD)',
                    'bank_transfer' => 'Bank Transfer',
                    'credit_card' => 'Credit Card',
                    'paypal' => 'PayPal'
                ],
                'default' => 'cod'
            ],
            [
                'key' => 'installments',
                'type' => 'checkbox',
                'label' => 'Interest in Installment Payment?',
                'description' => 'We can offer payment plans for higher value items'
            ]
        ];
    }

    /**
     * Get custom product-specific fields based on categories
     */
    private function get_custom_product_fields($product): array
    {
        $fields = [];
        $categories = $product->get_category_ids();

        // Add category-specific fields
        foreach ($categories as $category_id) {
            $category = get_term($category_id, 'product_cat');
            if ($category && !is_wp_error($category)) {
                $category_fields = $this->get_category_template_fields($category->slug);
                $fields = array_merge($fields, $category_fields);
            }
        }

        return $fields;
    }

    /**
     * Get category-specific template fields
     */
    private function get_category_template_fields(string $category_slug): array
    {
        $templates = [
            'electronics' => [
                [
                    'key' => 'warranty_preference',
                    'type' => 'select',
                    'label' => 'Warranty Preference',
                    'options' => [
                        '1_year' => '1 Year Standard',
                        '2_years' => '2 Years Extended',
                        '3_years' => '3 Years Premium'
                    ]
                ],
                [
                    'key' => 'technical_support',
                    'type' => 'checkbox',
                    'label' => 'Include Technical Support Package'
                ]
            ],
            'clothing' => [
                [
                    'key' => 'size_consultation',
                    'type' => 'checkbox',
                    'label' => 'Need Size Consultation?'
                ],
                [
                    'key' => 'gift_wrapping',
                    'type' => 'checkbox',
                    'label' => 'Gift Wrapping Required'
                ]
            ],
            'home-garden' => [
                [
                    'key' => 'assembly_service',
                    'type' => 'radio',
                    'label' => 'Assembly Service',
                    'options' => [
                        'none' => 'No Assembly Needed',
                        'self' => 'Self Assembly',
                        'professional' => 'Professional Assembly (+50 MAD)'
                    ]
                ]
            ]
        ];

        return $templates[$category_slug] ?? [];
    }

    /**
     * Render complete form HTML
     */
    public function render_complete_form(int $product_id, array $options = []): string
    {
        $form_fields = $this->generate_complete_woocommerce_form($product_id, $options);

        $html = '<form class="wccrm-product-form" data-product-id="' . $product_id . '">';

        foreach ($form_fields as $section_name => $fields) {
            if (empty($fields)) continue;

            $html .= '<div class="wccrm-form-section" data-section="' . $section_name . '">';
            $html .= '<h3 class="wccrm-section-title">' . $this->format_section_title($section_name) . '</h3>';

            foreach ($fields as $field) {
                $html .= $this->render_field_html($field);
            }

            $html .= '</div>';
        }

        $html .= '<button type="submit" class="wccrm-submit-btn">Submit Inquiry</button>';
        $html .= '</form>';

        return $html;
    }

    /**
     * Render individual field HTML
     */
    private function render_field_html(array $field): string
    {
        $type = $field['type'] ?? 'text';
        $key = $field['key'] ?? '';
        $label = $field['label'] ?? '';
        $required = $field['required'] ?? false;
        $value = $field['value'] ?? '';
        $readonly = $field['readonly'] ?? false;

        $html = '<div class="wccrm-field-wrapper" data-field="' . $key . '">';

        if ($label && $type !== 'hidden') {
            $html .= '<label for="' . $key . '" class="wccrm-field-label">';
            $html .= esc_html($label);
            if ($required) $html .= ' <span class="required">*</span>';
            $html .= '</label>';
        }

        switch ($type) {
            case 'hidden':
                $html .= '<input type="hidden" name="' . $key . '" value="' . esc_attr($value) . '">';
                break;

            case 'select':
                $html .= $this->render_select_field($field);
                break;

            case 'radio':
                $html .= $this->render_radio_field($field);
                break;

            case 'checkbox':
                $html .= $this->render_checkbox_field($field);
                break;

            default:
                $html .= '<input type="' . $type . '" name="' . $key . '" id="' . $key . '"';
                $html .= ' value="' . esc_attr($value) . '"';
                if ($required) $html .= ' required';
                if ($readonly) $html .= ' readonly';
                $html .= ' class="wccrm-field-input">';
        }

        if (!empty($field['description'])) {
            $html .= '<small class="wccrm-field-description">' . esc_html($field['description']) . '</small>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Render select field
     */
    private function render_select_field(array $field): string
    {
        $html = '<select name="' . $field['key'] . '" id="' . $field['key'] . '" class="wccrm-field-select"';
        if ($field['required'] ?? false) $html .= ' required';
        $html .= '>';

        $html .= '<option value="">Choose...</option>';

        foreach ($field['options'] ?? [] as $value => $label) {
            $selected = ($field['value'] ?? '') == $value ? ' selected' : '';
            $html .= '<option value="' . esc_attr($value) . '"' . $selected . '>' . esc_html($label) . '</option>';
        }

        $html .= '</select>';
        return $html;
    }

    /**
     * Render radio field
     */
    private function render_radio_field(array $field): string
    {
        $html = '<div class="wccrm-radio-group">';

        foreach ($field['options'] ?? [] as $value => $label) {
            $checked = ($field['default'] ?? '') == $value ? ' checked' : '';
            $html .= '<label class="wccrm-radio-label">';
            $html .= '<input type="radio" name="' . $field['key'] . '" value="' . esc_attr($value) . '"' . $checked . '>';
            $html .= ' ' . esc_html($label);
            $html .= '</label>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Render checkbox field
     */
    private function render_checkbox_field(array $field): string
    {
        $html = '<label class="wccrm-checkbox-label">';
        $html .= '<input type="checkbox" name="' . $field['key'] . '" value="1"';
        if ($field['default'] ?? false) $html .= ' checked';
        $html .= '>';
        $html .= ' ' . esc_html($field['label'] ?? '');
        $html .= '</label>';
        return $html;
    }

    /**
     * Format section title for display
     */
    private function format_section_title(string $section_name): string
    {
        $titles = [
            'product_info' => 'Product Information',
            'contact_fields' => 'Contact Details',
            'product_options' => 'Product Options',
            'shipping_fields' => 'Shipping Information',
            'payment_fields' => 'Payment Preferences',
            'custom_fields' => 'Additional Information'
        ];

        return $titles[$section_name] ?? ucfirst(str_replace('_', ' ', $section_name));
    }
}
