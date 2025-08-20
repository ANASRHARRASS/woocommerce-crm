<?php
/**
 * Dynamic Form Template
 * This template generates a dynamic form based on WooCommerce product attributes.
 */

// Enqueue necessary scripts and styles
wp_enqueue_style('woocommerce-crm-public', plugin_dir_url(__FILE__) . '../../assets/css/public.css');
wp_enqueue_script('woocommerce-crm-public', plugin_dir_url(__FILE__) . '../../assets/js/public.js', array('jquery'), null, true);

// Fetch WooCommerce product attributes
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$product = wc_get_product($product_id);

if ($product) {
    $attributes = $product->get_attributes();
    ?>
    <form id="dynamic-form" method="post" action="">
        <h2><?php echo esc_html($product->get_name()); ?></h2>
        <?php foreach ($attributes as $attribute) : ?>
            <div class="form-group">
                <label for="<?php echo esc_attr($attribute->get_name()); ?>">
                    <?php echo esc_html($attribute->get_name()); ?>
                </label>
                <select name="<?php echo esc_attr($attribute->get_name()); ?>" id="<?php echo esc_attr($attribute->get_name()); ?>">
                    <?php foreach ($attribute->get_options() as $option) : ?>
                        <option value="<?php echo esc_attr($option); ?>">
                            <?php echo esc_html($option); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endforeach; ?>
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
    <?php
} else {
    echo '<p>' . esc_html__('Product not found.', 'woocommerce-crm-plugin') . '</p>';
}
?>