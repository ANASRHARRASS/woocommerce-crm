<?php

/**
 * WooCommerce CRM Form Builder Demo
 * Shows how to use the premium form building features
 */

// Example 1: Generate complete product form like premium plugins
function wccrm_demo_product_form()
{
    if (!class_exists('Anas\\WCCRM\\Forms\\ProductFormFieldGenerator')) {
        return 'Form generator not available';
    }

    $formGenerator = new Anas\WCCRM\Forms\ProductFormFieldGenerator();

    // Get a WooCommerce product ID (replace with real product ID)
    $product_id = 123;

    // Generate complete form with all WooCommerce data
    $complete_form = $formGenerator->generate_complete_woocommerce_form($product_id, [
        'sections' => ['product_info', 'contact_fields', 'product_options', 'shipping_fields']
    ]);

    // Render the form HTML
    $form_html = $formGenerator->render_complete_form($product_id);

    return $form_html;
}

// Example 2: Custom form for specific product categories
function wccrm_demo_category_form($product_id)
{
    $formGenerator = new Anas\WCCRM\Forms\ProductFormFieldGenerator();

    // Generate form with category-specific fields
    $form_fields = $formGenerator->generate_product_fields($product_id);

    $html = '<div class="wccrm-custom-form">';

    foreach ($form_fields as $section => $fields) {
        $html .= '<div class="form-section">';
        $html .= '<h3>' . ucfirst(str_replace('_', ' ', $section)) . '</h3>';

        foreach ($fields as $field) {
            $html .= '<div class="field-wrapper">';
            $html .= '<label>' . $field['label'] . '</label>';

            switch ($field['type']) {
                case 'select':
                    $html .= '<select name="' . $field['key'] . '">';
                    foreach ($field['options'] as $value => $label) {
                        $html .= '<option value="' . $value . '">' . $label . '</option>';
                    }
                    $html .= '</select>';
                    break;

                case 'radio':
                    foreach ($field['options'] as $value => $label) {
                        $html .= '<label><input type="radio" name="' . $field['key'] . '" value="' . $value . '"> ' . $label . '</label>';
                    }
                    break;

                default:
                    $html .= '<input type="' . $field['type'] . '" name="' . $field['key'] . '" placeholder="' . ($field['placeholder'] ?? '') . '">';
            }

            $html .= '</div>';
        }

        $html .= '</div>';
    }

    $html .= '</div>';

    return $html;
}

// Example 3: AJAX form submission with Morocco validation
function wccrm_demo_ajax_form()
{
?>
    <script>
        jQuery(document).ready(function($) {
            $('.wccrm-product-form').on('submit', function(e) {
                e.preventDefault();

                var formData = $(this).serialize();
                var productId = $(this).data('product-id');

                // Add Morocco phone validation
                var phone = $('input[name="phone"]').val();
                if (phone) {
                    // Validate Morocco phone format
                    var phonePattern = /^(?:\+212|0)[567]\d{8}$/;
                    if (!phonePattern.test(phone.replace(/\s/g, ''))) {
                        alert('Please enter a valid Morocco phone number');
                        return;
                    }
                }

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wccrm_submit_product_form',
                        product_id: productId,
                        form_data: formData,
                        nonce: '<?php echo wp_create_nonce("wccrm_form_nonce"); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Form submitted successfully! We will contact you soon.');
                            $('.wccrm-product-form')[0].reset();
                        } else {
                            alert('Error: ' + response.data.message);
                        }
                    },
                    error: function() {
                        alert('An error occurred. Please try again.');
                    }
                });
            });
        });
    </script>
<?php
}

// Example 4: Shortcode for easy form embedding
function wccrm_product_form_shortcode($atts)
{
    $atts = shortcode_atts([
        'product_id' => 0,
        'sections' => 'all',
        'style' => 'default'
    ], $atts);

    if (empty($atts['product_id'])) {
        return 'Please specify a product ID';
    }

    $formGenerator = new Anas\WCCRM\Forms\ProductFormFieldGenerator();

    $sections = $atts['sections'] === 'all' ? [] : explode(',', $atts['sections']);
    $options = empty($sections) ? [] : ['sections' => $sections];

    $form_html = $formGenerator->render_complete_form($atts['product_id'], $options);

    // Add CSS based on style
    $css_class = 'wccrm-form-style-' . $atts['style'];
    $form_html = '<div class="' . $css_class . '">' . $form_html . '</div>';

    return $form_html;
}
add_shortcode('wccrm_product_form', 'wccrm_product_form_shortcode');

// Example 5: Form submission handler
function wccrm_handle_form_submission()
{
    if (!wp_verify_nonce($_POST['nonce'], 'wccrm_form_nonce')) {
        wp_die('Security check failed');
    }

    $product_id = intval($_POST['product_id']);
    $form_data = $_POST['form_data'];

    // Parse form data
    parse_str($form_data, $fields);

    // Validate Morocco phone if provided
    if (!empty($fields['phone'])) {
        $phone_validation = Anas\WCCRM\Utils\MoroccoValidator::validate_moroccan_phone($fields['phone']);
        if (!$phone_validation['valid']) {
            wp_send_json_error(['message' => 'Invalid phone number format']);
        }
        $fields['phone_formatted'] = $phone_validation['formatted'];
    }

    // Save to CRM
    $lead_data = [
        'source' => 'product_form',
        'product_id' => $product_id,
        'contact_data' => $fields,
        'created_at' => current_time('mysql')
    ];

    // Save to database or CRM system
    $saved = wccrm_save_lead($lead_data);

    if ($saved) {
        wp_send_json_success(['message' => 'Form submitted successfully']);
    } else {
        wp_send_json_error(['message' => 'Failed to save form data']);
    }
}
add_action('wp_ajax_wccrm_submit_product_form', 'wccrm_handle_form_submission');
add_action('wp_ajax_nopriv_wccrm_submit_product_form', 'wccrm_handle_form_submission');

// Example 6: Display form on product pages automatically
function wccrm_add_form_to_product_page()
{
    global $product;

    if (!$product) return;

    $formGenerator = new Anas\WCCRM\Forms\ProductFormFieldGenerator();
    $form_html = $formGenerator->render_complete_form($product->get_id(), [
        'sections' => ['contact_fields', 'product_options', 'shipping_fields']
    ]);

    echo '<div class="wccrm-product-inquiry">';
    echo '<h3>Product Inquiry Form</h3>';
    echo $form_html;
    echo '</div>';
}
// Uncomment to add form to all product pages
// add_action('woocommerce_single_product_summary', 'wccrm_add_form_to_product_page', 25);
