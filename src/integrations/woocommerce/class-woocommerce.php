<?php
// filepath: c:\Users\rharr\Downloads\kahckhat\KACHKHAT\universal-lead-capture-plugin\src\integrations\woocommerce\class-woocommerce.php

class WooCommerceIntegration {
    public function __construct() {
        add_action('woocommerce_after_order_notes', [$this, 'add_custom_fields']);
        add_action('woocommerce_checkout_update_order_meta', [$this, 'save_custom_fields']);
    }

    public function add_custom_fields($checkout) {
        echo '<div id="custom_checkout_field"><h2>' . __('Additional Information') . '</h2>';

        woocommerce_form_field('custom_field_1', [
            'type'          => 'text',
            'class'         => ['my-field-class form-row-wide'],
            'label'         => __('Custom Field 1'),
            'placeholder'   => __('Enter something'),
            ], $checkout->get_value('custom_field_1'));

        echo '</div>';
    }

    public function save_custom_fields($order_id) {
        if (!empty($_POST['custom_field_1'])) {
            update_post_meta($order_id, 'Custom Field 1', sanitize_text_field($_POST['custom_field_1']));
        }
    }
}
?>