<?php
/**
 * WhatsApp Templates system
 * Provides filtered templates for WhatsApp messaging
 */

namespace KS_CRM\WhatsApp;

defined( 'ABSPATH' ) || exit;

class Templates {

    /**
     * Get cart share template
     *
     * @param array $context Cart context data
     * @return string WhatsApp message template
     */
    public static function get_cart_share_template( array $context = [] ): string {
        $default_template = "Hi! I found these items in my cart and thought you might be interested:\n\n{cart_items}\n\nTotal: {cart_total}\n\nCheck it out: {cart_url}";

        $template = apply_filters( 'kscrm_whatsapp_template_cart_share', $default_template, $context );

        return self::replace_placeholders( $template, $context );
    }

    /**
     * Get order confirmation template
     *
     * @param array $context Order context data
     * @return string WhatsApp message template
     */
    public static function get_order_confirm_template( array $context = [] ): string {
        $default_template = "🎉 Thank you for your order!\n\nOrder #{order_number}\nTotal: {order_total}\nStatus: {order_status}\n\nWe'll send you updates as your order progresses.\n\nTrack your order: {order_url}";

        $template = apply_filters( 'kscrm_whatsapp_template_order_confirm', $default_template, $context );

        return self::replace_placeholders( $template, $context );
    }

    /**
     * Get product inquiry template
     *
     * @param array $context Product context data
     * @return string WhatsApp message template
     */
    public static function get_product_inquiry_template( array $context = [] ): string {
        $default_template = "Hi! I'm interested in this product:\n\n{product_name}\nPrice: {product_price}\n\n{product_url}\n\nCould you provide more information?";

        $template = apply_filters( 'kscrm_whatsapp_template_product_inquiry', $default_template, $context );

        return self::replace_placeholders( $template, $context );
    }

    /**
     * Get shipping update template
     *
     * @param array $context Shipping context data
     * @return string WhatsApp message template
     */
    public static function get_shipping_update_template( array $context = [] ): string {
        $default_template = "📦 Shipping Update\n\nOrder #{order_number}\nStatus: {shipping_status}\nTracking: {tracking_number}\n\nEstimated delivery: {estimated_delivery}\n\nTrack: {tracking_url}";

        $template = apply_filters( 'kscrm_whatsapp_template_shipping_update', $default_template, $context );

        return self::replace_placeholders( $template, $context );
    }

    /**
     * Get abandoned cart reminder template
     *
     * @param array $context Cart context data
     * @return string WhatsApp message template
     */
    public static function get_abandoned_cart_template( array $context = [] ): string {
        $default_template = "You left some items in your cart!\n\n{cart_items}\n\nDon't miss out - complete your purchase:\n{cart_url}\n\n{discount_offer}";

        $template = apply_filters( 'kscrm_whatsapp_template_abandoned_cart', $default_template, $context );

        return self::replace_placeholders( $template, $context );
    }

    /**
     * Get promotional template
     *
     * @param array $context Promotion context data
     * @return string WhatsApp message template
     */
    public static function get_promotional_template( array $context = [] ): string {
        $default_template = "🎁 Special Offer!\n\n{promotion_title}\n{promotion_description}\n\nCode: {coupon_code}\nValid until: {expiry_date}\n\nShop now: {shop_url}";

        $template = apply_filters( 'kscrm_whatsapp_template_promotional', $default_template, $context );

        return self::replace_placeholders( $template, $context );
    }

    /**
     * Get customer support template
     *
     * @param array $context Support context data
     * @return string WhatsApp message template
     */
    public static function get_support_template( array $context = [] ): string {
        $default_template = "Hello! Thank you for contacting us.\n\nRegarding: {inquiry_subject}\n\nOur support team will get back to you within {response_time}.\n\nReference: #{ticket_number}";

        $template = apply_filters( 'kscrm_whatsapp_template_support', $default_template, $context );

        return self::replace_placeholders( $template, $context );
    }

    /**
     * Get welcome message template
     *
     * @param array $context Customer context data
     * @return string WhatsApp message template
     */
    public static function get_welcome_template( array $context = [] ): string {
        $default_template = "Welcome to {shop_name}! 👋\n\nThank you for joining us, {customer_name}!\n\nDiscover our latest products and exclusive offers:\n{shop_url}\n\nNeed help? Just reply to this message!";

        $template = apply_filters( 'kscrm_whatsapp_template_welcome', $default_template, $context );

        return self::replace_placeholders( $template, $context );
    }

    /**
     * Get all available templates
     *
     * @return array Array of template names and their default patterns
     */
    public static function get_all_templates(): array {
        return [
            'cart_share' => [
                'name' => __( 'Cart Share', 'woocommerce-crm' ),
                'description' => __( 'Template for sharing cart contents via WhatsApp', 'woocommerce-crm' ),
                'default' => "Hi! I found these items in my cart and thought you might be interested:\n\n{cart_items}\n\nTotal: {cart_total}\n\nCheck it out: {cart_url}",
                'placeholders' => [ 'cart_items', 'cart_total', 'cart_url' ]
            ],
            'order_confirm' => [
                'name' => __( 'Order Confirmation', 'woocommerce-crm' ),
                'description' => __( 'Template for order confirmation messages', 'woocommerce-crm' ),
                'default' => "🎉 Thank you for your order!\n\nOrder #{order_number}\nTotal: {order_total}\nStatus: {order_status}\n\nWe'll send you updates as your order progresses.\n\nTrack your order: {order_url}",
                'placeholders' => [ 'order_number', 'order_total', 'order_status', 'order_url' ]
            ],
            'product_inquiry' => [
                'name' => __( 'Product Inquiry', 'woocommerce-crm' ),
                'description' => __( 'Template for product inquiry messages', 'woocommerce-crm' ),
                'default' => "Hi! I'm interested in this product:\n\n{product_name}\nPrice: {product_price}\n\n{product_url}\n\nCould you provide more information?",
                'placeholders' => [ 'product_name', 'product_price', 'product_url' ]
            ],
            'shipping_update' => [
                'name' => __( 'Shipping Update', 'woocommerce-crm' ),
                'description' => __( 'Template for shipping status updates', 'woocommerce-crm' ),
                'default' => "📦 Shipping Update\n\nOrder #{order_number}\nStatus: {shipping_status}\nTracking: {tracking_number}\n\nEstimated delivery: {estimated_delivery}\n\nTrack: {tracking_url}",
                'placeholders' => [ 'order_number', 'shipping_status', 'tracking_number', 'estimated_delivery', 'tracking_url' ]
            ],
            'abandoned_cart' => [
                'name' => __( 'Abandoned Cart', 'woocommerce-crm' ),
                'description' => __( 'Template for abandoned cart reminders', 'woocommerce-crm' ),
                'default' => "You left some items in your cart!\n\n{cart_items}\n\nDon't miss out - complete your purchase:\n{cart_url}\n\n{discount_offer}",
                'placeholders' => [ 'cart_items', 'cart_url', 'discount_offer' ]
            ],
            'promotional' => [
                'name' => __( 'Promotional', 'woocommerce-crm' ),
                'description' => __( 'Template for promotional messages', 'woocommerce-crm' ),
                'default' => "🎁 Special Offer!\n\n{promotion_title}\n{promotion_description}\n\nCode: {coupon_code}\nValid until: {expiry_date}\n\nShop now: {shop_url}",
                'placeholders' => [ 'promotion_title', 'promotion_description', 'coupon_code', 'expiry_date', 'shop_url' ]
            ],
            'support' => [
                'name' => __( 'Customer Support', 'woocommerce-crm' ),
                'description' => __( 'Template for customer support responses', 'woocommerce-crm' ),
                'default' => "Hello! Thank you for contacting us.\n\nRegarding: {inquiry_subject}\n\nOur support team will get back to you within {response_time}.\n\nReference: #{ticket_number}",
                'placeholders' => [ 'inquiry_subject', 'response_time', 'ticket_number' ]
            ],
            'welcome' => [
                'name' => __( 'Welcome Message', 'woocommerce-crm' ),
                'description' => __( 'Template for welcome messages to new customers', 'woocommerce-crm' ),
                'default' => "Welcome to {shop_name}! 👋\n\nThank you for joining us, {customer_name}!\n\nDiscover our latest products and exclusive offers:\n{shop_url}\n\nNeed help? Just reply to this message!",
                'placeholders' => [ 'shop_name', 'customer_name', 'shop_url' ]
            ]
        ];
    }

    /**
     * Replace placeholders in template with actual values
     *
     * @param string $template Template string with placeholders
     * @param array $context Context data for replacement
     * @return string Template with placeholders replaced
     */
    private static function replace_placeholders( string $template, array $context ): string {
        if ( empty( $context ) ) {
            return $template;
        }

        // Replace each placeholder
        foreach ( $context as $key => $value ) {
            $placeholder = '{' . $key . '}';
            
            // Handle array values
            if ( is_array( $value ) ) {
                $value = implode( "\n", $value );
            }
            
            // Ensure value is string
            $value = (string) $value;
            
            $template = str_replace( $placeholder, $value, $template );
        }

        // Clean up any remaining unreplaced placeholders
        $template = preg_replace( '/\{[^}]+\}/', '', $template );

        return trim( $template );
    }

    /**
     * Generate WhatsApp URL for sending message
     *
     * @param string $phone_number Phone number (with country code)
     * @param string $message Pre-filled message
     * @return string WhatsApp URL
     */
    public static function generate_whatsapp_url( string $phone_number, string $message ): string {
        // Clean phone number (remove non-digits except +)
        $phone_number = preg_replace( '/[^\d+]/', '', $phone_number );
        
        // Remove leading + if present
        $phone_number = ltrim( $phone_number, '+' );

        // URL encode the message
        $encoded_message = rawurlencode( $message );

        return "https://wa.me/{$phone_number}?text={$encoded_message}";
    }

    /**
     * Generate WhatsApp Web URL (no phone number)
     *
     * @param string $message Pre-filled message
     * @return string WhatsApp Web URL
     */
    public static function generate_whatsapp_web_url( string $message ): string {
        $encoded_message = rawurlencode( $message );
        return "https://web.whatsapp.com/send?text={$encoded_message}";
    }

    /**
     * Get default context values for testing templates
     *
     * @return array Default context values
     */
    public static function get_default_context(): array {
        return [
            // Shop info
            'shop_name' => get_bloginfo( 'name' ),
            'shop_url' => home_url(),
            
            // Customer info
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            
            // Order info
            'order_number' => '1234',
            'order_total' => '$99.99',
            'order_status' => 'Processing',
            'order_url' => home_url( '/my-account/orders/' ),
            
            // Cart info
            'cart_items' => "• Product A - $29.99\n• Product B - $39.99",
            'cart_total' => '$69.98',
            'cart_url' => wc_get_cart_url(),
            
            // Product info
            'product_name' => 'Sample Product',
            'product_price' => '$49.99',
            'product_url' => home_url( '/product/sample-product/' ),
            
            // Shipping info
            'shipping_status' => 'In Transit',
            'tracking_number' => 'TR123456789',
            'estimated_delivery' => date( 'M d, Y', strtotime( '+3 days' ) ),
            'tracking_url' => 'https://example.com/track/TR123456789',
            
            // Promotion info
            'promotion_title' => 'Summer Sale',
            'promotion_description' => 'Get 20% off all summer items!',
            'coupon_code' => 'SUMMER20',
            'expiry_date' => date( 'M d, Y', strtotime( '+7 days' ) ),
            'discount_offer' => 'Use code SAVE10 for 10% off!',
            
            // Support info
            'inquiry_subject' => 'Product Question',
            'response_time' => '24 hours',
            'ticket_number' => 'SP-789',
        ];
    }
}