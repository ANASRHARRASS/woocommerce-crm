<?php

namespace WooCommerceCRMPlugin\Forms;

use WooCommerceCRMPlugin\Utils\Helpers;

class ResellerForm {
    private $form_fields;

    public function __construct() {
        $this->form_fields = $this->generateFormFields();
    }

    private function generateFormFields() {
        // Dynamically generate fields based on WooCommerce product attributes
        $fields = [];
        // Example of adding fields based on product attributes
        $products = wc_get_products(['limit' => -1]);
        foreach ($products as $product) {
            $fields[] = [
                'label' => $product->get_name(),
                'name' => 'reseller_' . $product->get_id(),
                'type' => 'text',
                'required' => true,
            ];
        }
        return $fields;
    }

    public function render() {
        echo '<form method="post" action="">';
        
        // Add nonce field for security
        wp_nonce_field( 'reseller_form_submit', 'reseller_form_nonce' );
        
        foreach ($this->form_fields as $field) {
            echo '<label for="' . esc_attr($field['name']) . '">' . esc_html($field['label']) . '</label>';
            echo '<input type="' . esc_attr($field['type']) . '" name="' . esc_attr($field['name']) . '" id="' . esc_attr($field['name']) . '"' . ($field['required'] ? ' required' : '') . '>';
        }
        echo '<input type="submit" value="Submit">';
        echo '</form>';
    }

    public function handleSubmission() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Verify nonce for security
            if ( ! isset( $_POST['reseller_form_nonce'] ) || ! wp_verify_nonce( $_POST['reseller_form_nonce'], 'reseller_form_submit' ) ) {
                wp_die( 'Security check failed. Please try again.', 'Security Error', array( 'response' => 403 ) );
            }

            // Check user capabilities
            if ( ! current_user_can( 'read' ) ) {
                wp_die( 'You do not have permission to submit this form.', 'Permission Error', array( 'response' => 403 ) );
            }

            // Handle form submission logic here
            foreach ($this->form_fields as $field) {
                if (isset($_POST[$field['name']])) {
                    // Process the submitted data with proper sanitization
                    $value = sanitize_text_field($_POST[$field['name']]);
                    // Save or process the value as needed
                    // TODO: Implement actual form processing logic
                }
            }
            
            // Redirect to prevent resubmission
            wp_redirect( add_query_arg( 'form_submitted', '1', wp_get_referer() ) );
            exit;
        }
    }
}