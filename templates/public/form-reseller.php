<?php
/**
 * Reseller Form Template
 * This template displays the reseller form for users to fill out.
 */

// Check if WooCommerce is active
if ( ! class_exists( 'WooCommerce' ) ) {
    return;
}

// Enqueue necessary scripts and styles
wp_enqueue_style( 'public-css', plugin_dir_url( __FILE__ ) . '../../assets/css/public.css' );
wp_enqueue_script( 'public-js', plugin_dir_url( __FILE__ ) . '../../assets/js/public.js', array( 'jquery' ), null, true );

// Function to dynamically generate form fields based on WooCommerce product attributes
function generate_dynamic_fields( $product_id ) {
    $product = wc_get_product( $product_id );
    $attributes = $product->get_attributes();
    $fields = '';

    foreach ( $attributes as $attribute ) {
        $fields .= '<div class="form-group">';
        $fields .= '<label for="' . esc_attr( $attribute->get_name() ) . '">' . esc_html( $attribute->get_name() ) . '</label>';
        $fields .= '<input type="text" name="' . esc_attr( $attribute->get_name() ) . '" id="' . esc_attr( $attribute->get_name() ) . '" class="form-control" />';
        $fields .= '</div>';
    }

    return $fields;
}

// Handle form submission
if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['reseller_form_submit'] ) ) {
    // Process form data and save to CRM
    // Add your integration logic here for HubSpot and Zoho CRM
}

// Display the form
?>
<form method="post" class="reseller-form">
    <h2>Reseller Application Form</h2>
    <?php echo generate_dynamic_fields( get_the_ID() ); ?>
    <div class="form-group">
        <input type="submit" name="reseller_form_submit" value="Submit" class="btn btn-primary" />
    </div>
</form>