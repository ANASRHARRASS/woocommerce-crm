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
        foreach ($this->form_fields as $field) {
            echo '<label for="' . esc_attr($field['name']) . '">' . esc_html($field['label']) . '</label>';
            echo '<input type="' . esc_attr($field['type']) . '" name="' . esc_attr($field['name']) . '" required="' . ($field['required'] ? 'required' : '') . '">';
        }
        echo '<input type="submit" value="Submit">';
        echo '</form>';
    }

    public function handleSubmission() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Handle form submission logic here
            foreach ($this->form_fields as $field) {
                if (isset($_POST[$field['name']])) {
                    // Process the submitted data
                    $value = sanitize_text_field($_POST[$field['name']]);
                    // Save or process the value as needed
                }
            }
        }
    }
}