<?php
namespace KS_CRM\Products;

defined( 'ABSPATH' ) || exit;

class Product_Search_REST {
    
    private $namespace = 'ks-crm/v1';
    
    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }
    
    public function register_routes() {
        register_rest_route( $this->namespace, '/product-search', [
            'methods' => 'GET',
            'callback' => [ $this, 'search_products' ],
            'permission_callback' => [ $this, 'permission_callback' ],
            'args' => [
                's' => [
                    'required' => true,
                    'validate_callback' => function( $param ) {
                        return is_string( $param ) && strlen( $param ) >= 2;
                    },
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'page' => [
                    'default' => 1,
                    'validate_callback' => function( $param ) {
                        return is_numeric( $param ) && $param > 0;
                    },
                    'sanitize_callback' => 'absint',
                ],
                'per_page' => [
                    'default' => 10,
                    'validate_callback' => function( $param ) {
                        return is_numeric( $param ) && $param > 0 && $param <= 50;
                    },
                    'sanitize_callback' => 'absint',
                ],
            ],
        ] );
    }
    
    public function permission_callback( $request ) {
        // Apply rate limiting
        $this->apply_rate_limiting( $request );
        
        // For now, allow all requests but in production you might want to restrict this
        return true;
    }
    
    public function search_products( $request ) {
        $search_term = $request->get_param( 's' );
        $page = $request->get_param( 'page' );
        $per_page = $request->get_param( 'per_page' );
        
        if ( ! function_exists( 'wc_get_products' ) ) {
            return new \WP_REST_Response( [
                'error' => 'WooCommerce not available',
                'products' => [],
                'total' => 0,
            ], 500 );
        }
        
        return $this->search_products_internal( $search_term, $page, $per_page );
    }
    
    public function search_products_internal( $search_term, $page = 1, $per_page = 10 ) {
        $args = [
            'status' => 'publish',
            'limit' => $per_page,
            'page' => $page,
            's' => $search_term,
            'orderby' => 'relevance',
            'order' => 'DESC',
        ];
        
        $products = wc_get_products( $args );
        $total_products = wc_get_products( array_merge( $args, [ 'limit' => -1, 'return' => 'ids' ] ) );
        $total = count( $total_products );
        
        $formatted_products = [];
        
        foreach ( $products as $product ) {
            $formatted_products[] = $this->format_product( $product );
        }
        
        return [
            'products' => $formatted_products,
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil( $total / $per_page ),
        ];
    }
    
    private function format_product( $product ) {
        if ( ! $product instanceof \WC_Product ) {
            return null;
        }
        
        $image_id = $product->get_image_id();
        $thumbnail = '';
        
        if ( $image_id ) {
            $thumbnail = wp_get_attachment_image_url( $image_id, 'thumbnail' );
        }
        
        // Get price with currency symbol
        $price_html = $product->get_price_html();
        $regular_price = $product->get_regular_price();
        $sale_price = $product->get_sale_price();
        
        return [
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'sku' => $product->get_sku(),
            'price' => $regular_price,
            'sale_price' => $sale_price,
            'price_html' => wp_strip_all_tags( $price_html ),
            'formatted_price' => wc_price( $regular_price ),
            'formatted_sale_price' => $sale_price ? wc_price( $sale_price ) : '',
            'permalink' => $product->get_permalink(),
            'thumbnail' => $thumbnail,
            'in_stock' => $product->is_in_stock(),
            'stock_quantity' => $product->get_stock_quantity(),
            'manage_stock' => $product->get_manage_stock(),
            'type' => $product->get_type(),
            'short_description' => wp_trim_words( $product->get_short_description(), 20 ),
        ];
    }
    
    private function apply_rate_limiting( $request ) {
        $client_ip = $this->get_client_ip();
        $rate_limit_key = 'ks_crm_rate_limit_' . md5( $client_ip );
        
        $requests = get_transient( $rate_limit_key );
        
        if ( false === $requests ) {
            // First request in this time window
            set_transient( $rate_limit_key, 1, MINUTE_IN_SECONDS );
        } else {
            $requests = intval( $requests );
            
            // Allow up to 60 requests per minute
            if ( $requests >= 60 ) {
                wp_die( 
                    __( 'Rate limit exceeded. Please try again later.', 'woocommerce-crm' ),
                    __( 'Too Many Requests', 'woocommerce-crm' ),
                    [ 'response' => 429 ]
                );
            }
            
            set_transient( $rate_limit_key, $requests + 1, MINUTE_IN_SECONDS );
        }
        
        // Apply filter for custom rate limiting
        do_action( 'ks_crm_product_search_rate_limit', $client_ip, $requests );
    }
    
    private function get_client_ip() {
        $ip_keys = [ 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR' ];
        
        foreach ( $ip_keys as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) {
                $ip = $_SERVER[ $key ];
                
                // Handle comma-separated IPs (from proxies)
                if ( strpos( $ip, ',' ) !== false ) {
                    $ip = explode( ',', $ip )[0];
                }
                
                $ip = trim( $ip );
                
                // Validate IP
                if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
                    return $ip;
                }
            }
        }
        
        return '127.0.0.1'; // Fallback
    }
}