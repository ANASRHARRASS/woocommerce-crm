<?php
namespace KS_CRM\Cart;

defined( 'ABSPATH' ) || exit;

class Cart_Link_Handler {
    
    public function __construct() {
        add_action( 'init', [ $this, 'handle_cart_link' ] );
        add_action( 'woocommerce_cart_loaded_from_session', [ $this, 'handle_cart_redirect' ] );
    }
    
    public function handle_cart_link() {
        // Check if we have the kscrm_add parameter
        if ( ! isset( $_GET['kscrm_add'] ) ) {
            return;
        }
        
        // Only handle if WooCommerce is available
        if ( ! function_exists( 'WC' ) ) {
            return;
        }
        
        $products_data = sanitize_text_field( $_GET['kscrm_add'] );
        
        if ( empty( $products_data ) ) {
            return;
        }
        
        // Parse the product data: ID:qty,ID2:qty2
        $products = $this->parse_products_data( $products_data );
        
        if ( empty( $products ) ) {
            return;
        }
        
        // Clear existing cart
        WC()->cart->empty_cart();
        
        // Add products to cart
        $success_count = 0;
        $error_count = 0;
        
        foreach ( $products as $product_id => $quantity ) {
            $result = WC()->cart->add_to_cart( $product_id, $quantity );
            
            if ( $result ) {
                $success_count++;
            } else {
                $error_count++;
            }
        }
        
        // Set session flag for redirect handling
        WC()->session->set( 'ks_crm_cart_build', [
            'success' => $success_count,
            'errors' => $error_count,
            'total_products' => count( $products ),
        ] );
        
        // Redirect to cart page
        wp_safe_redirect( wc_get_cart_url() );
        exit;
    }
    
    public function handle_cart_redirect() {
        $cart_build_data = WC()->session->get( 'ks_crm_cart_build' );
        
        if ( ! $cart_build_data ) {
            return;
        }
        
        // Clear the session data
        WC()->session->__unset( 'ks_crm_cart_build' );
        
        $success_count = $cart_build_data['success'];
        $error_count = $cart_build_data['errors'];
        $total_products = $cart_build_data['total_products'];
        
        if ( $success_count > 0 ) {
            $message = sprintf(
                _n(
                    '%d product has been added to your cart.',
                    '%d products have been added to your cart.',
                    $success_count,
                    'woocommerce-crm'
                ),
                $success_count
            );
            
            wc_add_notice( $message, 'success' );
        }
        
        if ( $error_count > 0 ) {
            $message = sprintf(
                _n(
                    '%d product could not be added to your cart.',
                    '%d products could not be added to your cart.',
                    $error_count,
                    'woocommerce-crm'
                ),
                $error_count
            );
            
            wc_add_notice( $message, 'error' );
        }
        
        // Log for debugging
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( sprintf(
                '[KS_CRM] Cart build completed: %d success, %d errors out of %d total products',
                $success_count,
                $error_count,
                $total_products
            ) );
        }
    }
    
    private function parse_products_data( $products_data ) {
        $products = [];
        
        // Split by comma to get individual product entries
        $product_entries = explode( ',', $products_data );
        
        foreach ( $product_entries as $entry ) {
            $entry = trim( $entry );
            
            if ( empty( $entry ) ) {
                continue;
            }
            
            // Split by colon to get product ID and quantity
            $parts = explode( ':', $entry );
            
            if ( count( $parts ) !== 2 ) {
                continue;
            }
            
            $product_id = intval( trim( $parts[0] ) );
            $quantity = intval( trim( $parts[1] ) );
            
            // Validate product ID and quantity
            if ( $product_id <= 0 || $quantity <= 0 ) {
                continue;
            }
            
            // Check if product exists and is purchasable
            $product = wc_get_product( $product_id );
            
            if ( ! $product || ! $product->is_purchasable() ) {
                continue;
            }
            
            // Limit quantity to reasonable amount
            $quantity = min( $quantity, 99 );
            
            $products[ $product_id ] = $quantity;
        }
        
        return $products;
    }
    
    /**
     * Generate a cart link for multiple products
     * 
     * @param array $products Array of product_id => quantity pairs
     * @return string The cart link URL
     */
    public static function generate_cart_link( array $products ) {
        if ( empty( $products ) ) {
            return wc_get_cart_url();
        }
        
        $product_strings = [];
        
        foreach ( $products as $product_id => $quantity ) {
            $product_id = intval( $product_id );
            $quantity = intval( $quantity );
            
            if ( $product_id > 0 && $quantity > 0 ) {
                $product_strings[] = $product_id . ':' . $quantity;
            }
        }
        
        if ( empty( $product_strings ) ) {
            return wc_get_cart_url();
        }
        
        $products_data = implode( ',', $product_strings );
        
        return add_query_arg( 'kscrm_add', $products_data, home_url( '/' ) );
    }
    
    /**
     * Get WhatsApp message format for cart
     * 
     * @param array $products Array of product data
     * @param array $totals Array with subtotal, shipping, total
     * @param string $cart_link The cart link URL
     * @return string The formatted WhatsApp message
     */
    public static function get_whatsapp_message( array $products, array $totals, string $cart_link ) {
        $message = __( 'Order Inquiry:', 'woocommerce-crm' ) . "\n";
        
        foreach ( $products as $product ) {
            $line_total = $product['price'] * $product['quantity'];
            $message .= sprintf(
                "- %s x %d = %s\n",
                $product['name'],
                $product['quantity'],
                wc_price( $line_total )
            );
        }
        
        $message .= "\n";
        $message .= __( 'Subtotal: ', 'woocommerce-crm' ) . wc_price( $totals['subtotal'] ) . "\n";
        
        if ( isset( $totals['shipping'] ) && $totals['shipping'] > 0 ) {
            $message .= __( 'Shipping: ', 'woocommerce-crm' ) . wc_price( $totals['shipping'] ) . "\n";
        } else {
            $message .= __( 'Shipping: ', 'woocommerce-crm' ) . __( 'TBD', 'woocommerce-crm' ) . "\n";
        }
        
        $message .= __( 'Total: ', 'woocommerce-crm' ) . wc_price( $totals['total'] ) . "\n\n";
        $message .= __( 'Cart: ', 'woocommerce-crm' ) . $cart_link . "\n";
        
        return $message;
    }
    
    /**
     * Get WhatsApp link with pre-filled message
     * 
     * @param string $message The message to send
     * @param string $phone_number Optional WhatsApp number (from settings)
     * @return string The WhatsApp URL
     */
    public static function get_whatsapp_link( string $message, string $phone_number = '' ) {
        // Get WhatsApp number from settings if not provided
        if ( empty( $phone_number ) ) {
            $phone_number = get_option( 'ks_crm_whatsapp_number', '' );
        }
        
        $encoded_message = urlencode( $message );
        
        if ( ! empty( $phone_number ) ) {
            // Remove any non-numeric characters from phone number
            $phone_number = preg_replace( '/[^0-9]/', '', $phone_number );
            return "https://wa.me/{$phone_number}?text={$encoded_message}";
        } else {
            // Generic WhatsApp link without specific number
            return "https://wa.me/?text={$encoded_message}";
        }
    }
}