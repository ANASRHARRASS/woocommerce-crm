<?php
namespace KS_CRM\WhatsApp;

defined( 'ABSPATH' ) || exit;

class Cart_Share {
    
    public function __construct() {
        // This class provides utility methods for WhatsApp cart sharing
        // Most functionality will be handled through the Elementor widget
        add_action( 'init', [ $this, 'init' ] );
    }
    
    public function init() {
        // Register WhatsApp settings
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }
    
    public function register_settings() {
        // Add settings section for WhatsApp
        add_settings_section(
            'ks_crm_whatsapp_settings',
            __( 'WhatsApp Settings', 'woocommerce-crm' ),
            [ $this, 'whatsapp_settings_callback' ],
            'general'
        );
        
        // WhatsApp number setting
        add_settings_field(
            'ks_crm_whatsapp_number',
            __( 'WhatsApp Number', 'woocommerce-crm' ),
            [ $this, 'whatsapp_number_callback' ],
            'general',
            'ks_crm_whatsapp_settings'
        );
        
        register_setting( 'general', 'ks_crm_whatsapp_number', [
            'type' => 'string',
            'sanitize_callback' => [ $this, 'sanitize_phone_number' ],
        ] );
    }
    
    public function whatsapp_settings_callback() {
        echo '<p>' . esc_html__( 'Configure WhatsApp integration settings.', 'woocommerce-crm' ) . '</p>';
    }
    
    public function whatsapp_number_callback() {
        $value = get_option( 'ks_crm_whatsapp_number', '' );
        echo '<input type="text" id="ks_crm_whatsapp_number" name="ks_crm_whatsapp_number" value="' . esc_attr( $value ) . '" class="regular-text" placeholder="+1234567890" />';
        echo '<p class="description">' . esc_html__( 'Enter your WhatsApp number with country code (e.g., +1234567890)', 'woocommerce-crm' ) . '</p>';
    }
    
    public function sanitize_phone_number( $phone ) {
        // Remove all non-digit characters except +
        $phone = preg_replace( '/[^0-9+]/', '', $phone );
        
        // Ensure it starts with + if there are digits
        if ( ! empty( $phone ) && $phone[0] !== '+' ) {
            $phone = '+' . $phone;
        }
        
        return $phone;
    }
    
    /**
     * Get current cart data formatted for WhatsApp sharing
     * 
     * @return array|false Cart data or false if cart is empty
     */
    public static function get_cart_data() {
        if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
            return false;
        }
        
        $cart = WC()->cart;
        
        if ( $cart->is_empty() ) {
            return false;
        }
        
        $cart_items = [];
        $subtotal = 0;
        
        foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
            $product = $cart_item['data'];
            $quantity = $cart_item['quantity'];
            $line_total = $cart_item['line_total'];
            
            $cart_items[] = [
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'quantity' => $quantity,
                'price' => $product->get_price(),
                'line_total' => $line_total,
                'formatted_price' => wc_price( $product->get_price() ),
                'formatted_line_total' => wc_price( $line_total ),
            ];
            
            $subtotal += $line_total;
        }
        
        // Get shipping information
        $shipping_total = $cart->get_shipping_total();
        $shipping_tax = $cart->get_shipping_tax();
        $total_shipping = $shipping_total + $shipping_tax;
        
        // Calculate total
        $total = $cart->get_total( 'raw' );
        
        return [
            'items' => $cart_items,
            'subtotal' => $subtotal,
            'shipping' => $total_shipping,
            'total' => $total,
            'formatted_subtotal' => wc_price( $subtotal ),
            'formatted_shipping' => $total_shipping > 0 ? wc_price( $total_shipping ) : __( 'TBD', 'woocommerce-crm' ),
            'formatted_total' => wc_price( $total ),
            'has_shipping' => $total_shipping > 0,
        ];
    }
    
    /**
     * Generate cart link for current cart contents
     * 
     * @return string|false Cart link or false if cart is empty
     */
    public static function get_cart_link() {
        $cart_data = self::get_cart_data();
        
        if ( ! $cart_data ) {
            return false;
        }
        
        $products = [];
        
        foreach ( $cart_data['items'] as $item ) {
            $products[ $item['id'] ] = $item['quantity'];
        }
        
        if ( class_exists( 'KS_CRM\Cart\Cart_Link_Handler' ) ) {
            return \KS_CRM\Cart\Cart_Link_Handler::generate_cart_link( $products );
        }
        
        return wc_get_cart_url();
    }
    
    /**
     * Generate WhatsApp message for current cart
     * 
     * @return string|false WhatsApp message or false if cart is empty
     */
    public static function get_whatsapp_message() {
        $cart_data = self::get_cart_data();
        
        if ( ! $cart_data ) {
            return false;
        }
        
        $cart_link = self::get_cart_link();
        
        $message = __( 'Cart Inquiry:', 'woocommerce-crm' ) . "\n";
        
        foreach ( $cart_data['items'] as $item ) {
            $message .= sprintf(
                "- %s x %d = %s\n",
                $item['name'],
                $item['quantity'],
                $item['formatted_line_total']
            );
        }
        
        $message .= "\n";
        $message .= __( 'Subtotal: ', 'woocommerce-crm' ) . $cart_data['formatted_subtotal'] . "\n";
        $message .= __( 'Shipping: ', 'woocommerce-crm' ) . $cart_data['formatted_shipping'] . "\n";
        $message .= __( 'Total: ', 'woocommerce-crm' ) . $cart_data['formatted_total'] . "\n\n";
        $message .= __( 'Cart: ', 'woocommerce-crm' ) . $cart_link . "\n";
        
        return $message;
    }
    
    /**
     * Generate WhatsApp link for current cart
     * 
     * @return string|false WhatsApp link or false if cart is empty
     */
    public static function get_whatsapp_link() {
        $message = self::get_whatsapp_message();
        
        if ( ! $message ) {
            return false;
        }
        
        $phone_number = get_option( 'ks_crm_whatsapp_number', '' );
        
        if ( class_exists( 'KS_CRM\Cart\Cart_Link_Handler' ) ) {
            return \KS_CRM\Cart\Cart_Link_Handler::get_whatsapp_link( $message, $phone_number );
        }
        
        // Fallback implementation
        $encoded_message = urlencode( $message );
        
        if ( ! empty( $phone_number ) ) {
            $phone_number = preg_replace( '/[^0-9]/', '', $phone_number );
            return "https://wa.me/{$phone_number}?text={$encoded_message}";
        } else {
            return "https://wa.me/?text={$encoded_message}";
        }
    }
    
    /**
     * Get fallback message when cart is empty or shipping not available
     * 
     * @param string $type Type of fallback message
     * @return string Fallback message
     */
    public static function get_fallback_message( $type = 'empty_cart' ) {
        switch ( $type ) {
            case 'empty_cart':
                return __( 'Hi! I\'m interested in your products. Could you help me with some information?', 'woocommerce-crm' );
                
            case 'no_shipping':
                return __( 'Hi! I have items in my cart but need shipping information. Could you help?', 'woocommerce-crm' );
                
            default:
                return __( 'Hi! I\'m interested in making a purchase. Could you assist me?', 'woocommerce-crm' );
        }
    }
    
    /**
     * Get WhatsApp link with fallback message
     * 
     * @param string $type Type of fallback message
     * @return string WhatsApp link with fallback message
     */
    public static function get_fallback_whatsapp_link( $type = 'empty_cart' ) {
        $message = self::get_fallback_message( $type );
        $phone_number = get_option( 'ks_crm_whatsapp_number', '' );
        
        if ( class_exists( 'KS_CRM\Cart\Cart_Link_Handler' ) ) {
            return \KS_CRM\Cart\Cart_Link_Handler::get_whatsapp_link( $message, $phone_number );
        }
        
        // Fallback implementation
        $encoded_message = urlencode( $message );
        
        if ( ! empty( $phone_number ) ) {
            $phone_number = preg_replace( '/[^0-9]/', '', $phone_number );
            return "https://wa.me/{$phone_number}?text={$encoded_message}";
        } else {
            return "https://wa.me/?text={$encoded_message}";
        }
    }
}