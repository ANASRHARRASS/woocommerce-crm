<?php
/**
 * Contact Form Template
 *
 * This template displays the contact form for users to fill out.
 * It dynamically generates fields based on WooCommerce product attributes.
 */

defined( 'ABSPATH' ) || exit;

// Enqueue necessary scripts and styles
wp_enqueue_style( 'public-style', plugin_dir_url( __FILE__ ) . '../../assets/css/public.css' );
wp_enqueue_script( 'public-script', plugin_dir_url( __FILE__ ) . '../../assets/js/public.js', array( 'jquery' ), null, true );

// Check if WooCommerce is active
if ( ! class_exists( 'WooCommerce' ) ) {
    echo '<p>' . esc_html__( 'WooCommerce is not active. Please activate WooCommerce to use this feature.', 'woocommerce-crm-plugin' ) . '</p>';
    return;
}

// Handle form submission
if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['contact_form_submit'] ) ) {
    // Validate and process the form data here
    // Example: Capture leads, send to HubSpot or Zoho, etc.
}

// Get WooCommerce product attributes for dynamic fields
$product_attributes = []; // Fetch product attributes logic here

?>

<form id="contact-form" method="post" action="">
    <h2><?php esc_html_e( 'Contact Us', 'woocommerce-crm-plugin' ); ?></h2>

    <div class="form-group">
        <label for="name"><?php esc_html_e( 'Name', 'woocommerce-crm-plugin' ); ?></label>
        <input type="text" id="name" name="name" required>
    </div>

    <div class="form-group">
        <label for="email"><?php esc_html_e( 'Email', 'woocommerce-crm-plugin' ); ?></label>
        <input type="email" id="email" name="email" required>
    </div>

    <div class="form-group">
        <label for="message"><?php esc_html_e( 'Message', 'woocommerce-crm-plugin' ); ?></label>
        <textarea id="message" name="message" required></textarea>
    </div>

    <?php foreach ( $product_attributes as $attribute ) : ?>
        <div class="form-group">
            <label for="<?php echo esc_attr( $attribute['name'] ); ?>"><?php echo esc_html( $attribute['label'] ); ?></label>
            <input type="text" id="<?php echo esc_attr( $attribute['name'] ); ?>" name="<?php echo esc_attr( $attribute['name'] ); ?>">
        </div>
    <?php endforeach; ?>

    <input type="submit" name="contact_form_submit" value="<?php esc_attr_e( 'Submit', 'woocommerce-crm-plugin' ); ?>">
</form>