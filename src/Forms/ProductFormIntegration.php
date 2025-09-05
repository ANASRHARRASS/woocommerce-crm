<?php

namespace Anas\WCCRM\Forms;

defined('ABSPATH') || exit;

/**
 * Per-product CRM form selection + automatic render on product pages.
 */
class ProductFormIntegration
{
    public function register(): void
    {
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('save_post_product', [$this, 'save_meta']);
        add_action('woocommerce_single_product_summary', [$this, 'output_form'], 35);
    }

    public function add_meta_box(): void
    {
        add_meta_box('wccrm_product_form', __('CRM Form', 'woocommerce-crm'), [$this, 'render_meta_box'], 'product', 'side');
    }

    public function render_meta_box(\WP_Post $post): void
    {
        wp_nonce_field('wccrm_product_form', '_wccrm_pf_nonce');
        $current = get_post_meta($post->ID, '_wccrm_form_key', true);
        $repo = new FormRepository();
        $forms = $repo->list_active();
        echo '<p><select name="_wccrm_form_key" style="width:100%">';
        echo '<option value="">' . esc_html__('— None —', 'woocommerce-crm') . '</option>';
        foreach ($forms as $form) {
            $sel = selected($current, $form->form_key, false);
            echo '<option value="' . esc_attr($form->form_key) . '" ' . $sel . '>' . esc_html($form->name) . ' (' . esc_html($form->form_key) . ')</option>';
        }
        echo '</select></p>';
        echo '<p class="description">' . esc_html__('Selected form will appear under product summary.', 'woocommerce-crm') . '</p>';
    }

    public function save_meta(int $post_id): void
    {
        if (!isset($_POST['_wccrm_pf_nonce']) || !wp_verify_nonce($_POST['_wccrm_pf_nonce'], 'wccrm_product_form')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        $val = sanitize_key($_POST['_wccrm_form_key'] ?? '');
        if ($val) update_post_meta($post_id, '_wccrm_form_key', $val);
        else delete_post_meta($post_id, '_wccrm_form_key');
    }

    public function output_form(): void
    {
        global $post;
        if (!$post || $post->post_type !== 'product') return;
        $key = get_post_meta($post->ID, '_wccrm_form_key', true);
        if (!$key) return;
        echo '<div class="wccrm-product-form">' . do_shortcode('[wccrm_form key="' . esc_attr($key) . '"]') . '</div>';
    }
}
