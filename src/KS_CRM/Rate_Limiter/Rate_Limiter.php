<?php
/**
 * Rate Limiter for public endpoints
 * Simple IP-based throttling using transient counters
 */

namespace KS_CRM\Rate_Limiter;

defined( 'ABSPATH' ) || exit;

class Rate_Limiter {

    /**
     * Check if request is within rate limit
     *
     * @param string $endpoint Endpoint identifier (e.g., 'news', 'api')
     * @param int $max_requests Maximum requests allowed
     * @param int $time_window Time window in seconds
     * @param string $identifier Optional identifier (defaults to IP)
     * @return bool True if within limits, false if exceeded
     */
    public static function is_allowed( string $endpoint, int $max_requests = 30, int $time_window = 600, string $identifier = '' ): bool {
        $identifier = $identifier ?: self::get_client_identifier();
        $cache_key = self::build_cache_key( $endpoint, $identifier );
        
        $current_data = get_transient( $cache_key );
        
        if ( $current_data === false ) {
            // First request in window
            self::init_rate_limit( $cache_key, $time_window );
            return true;
        }
        
        $current_data = maybe_unserialize( $current_data );
        $count = intval( $current_data['count'] ?? 0 );
        $first_timestamp = intval( $current_data['first_timestamp'] ?? time() );
        
        // Check if time window has passed
        if ( time() - $first_timestamp >= $time_window ) {
            // Reset window
            self::init_rate_limit( $cache_key, $time_window );
            return true;
        }
        
        // Check if within limit
        if ( $count >= $max_requests ) {
            return false;
        }
        
        // Increment counter
        self::increment_counter( $cache_key, $current_data, $time_window );
        return true;
    }

    /**
     * Get rate limit status for endpoint
     *
     * @param string $endpoint Endpoint identifier
     * @param string $identifier Optional identifier (defaults to IP)
     * @return array Rate limit status information
     */
    public static function get_status( string $endpoint, string $identifier = '' ): array {
        $identifier = $identifier ?: self::get_client_identifier();
        $cache_key = self::build_cache_key( $endpoint, $identifier );
        
        $current_data = get_transient( $cache_key );
        
        if ( $current_data === false ) {
            return [
                'count' => 0,
                'remaining' => 30, // Default max
                'reset_time' => time() + 600, // Default window
                'limited' => false,
            ];
        }
        
        $current_data = maybe_unserialize( $current_data );
        $count = intval( $current_data['count'] ?? 0 );
        $first_timestamp = intval( $current_data['first_timestamp'] ?? time() );
        $window = intval( $current_data['window'] ?? 600 );
        $max_requests = intval( $current_data['max_requests'] ?? 30 );
        
        return [
            'count' => $count,
            'remaining' => max( 0, $max_requests - $count ),
            'reset_time' => $first_timestamp + $window,
            'limited' => $count >= $max_requests,
        ];
    }

    /**
     * Create rate limit error response
     *
     * @param string $endpoint Endpoint identifier
     * @param string $identifier Optional identifier
     * @return \WP_Error Rate limit error
     */
    public static function create_error( string $endpoint, string $identifier = '' ): \WP_Error {
        $status = self::get_status( $endpoint, $identifier );
        
        return new \WP_Error(
            'rate_limited',
            sprintf(
                'Rate limit exceeded. Try again in %d seconds.',
                max( 0, $status['reset_time'] - time() )
            ),
            [
                'status' => 429,
                'headers' => [
                    'X-RateLimit-Limit' => $status['count'] + $status['remaining'],
                    'X-RateLimit-Remaining' => $status['remaining'],
                    'X-RateLimit-Reset' => $status['reset_time'],
                ]
            ]
        );
    }

    /**
     * Get client identifier (IP address with hashing for privacy)
     *
     * @return string Hashed client identifier
     */
    private static function get_client_identifier(): string {
        $ip = '';
        
        // Try various headers for real IP
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_REAL_IP',           // Nginx proxy
            'HTTP_X_FORWARDED_FOR',     // Load balancer/proxy
            'HTTP_X_FORWARDED',         // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP', // Cluster
            'HTTP_FORWARDED_FOR',       // Proxy
            'HTTP_FORWARDED',           // RFC 7239
            'REMOTE_ADDR'               // Standard
        ];
        
        foreach ( $headers as $header ) {
            if ( ! empty( $_SERVER[ $header ] ) ) {
                $ip = $_SERVER[ $header ];
                break;
            }
        }
        
        // Handle comma-separated IPs (X-Forwarded-For)
        if ( strpos( $ip, ',' ) !== false ) {
            $ip = trim( explode( ',', $ip )[0] );
        }
        
        // Validate and hash IP for privacy
        $ip = filter_var( $ip, FILTER_VALIDATE_IP );
        return $ip ? hash( 'sha256', $ip . NONCE_SALT ) : 'unknown';
    }

    /**
     * Build cache key for rate limiting
     *
     * @param string $endpoint Endpoint identifier
     * @param string $identifier Client identifier
     * @return string Cache key
     */
    private static function build_cache_key( string $endpoint, string $identifier ): string {
        return 'kscrm_rl_' . sanitize_key( $endpoint ) . '_' . substr( $identifier, 0, 12 );
    }

    /**
     * Initialize rate limit counter
     *
     * @param string $cache_key Cache key
     * @param int $time_window Time window in seconds
     */
    private static function init_rate_limit( string $cache_key, int $time_window ): void {
        $data = [
            'count' => 1,
            'first_timestamp' => time(),
            'window' => $time_window,
        ];
        
        set_transient( $cache_key, maybe_serialize( $data ), $time_window );
    }

    /**
     * Increment rate limit counter
     *
     * @param string $cache_key Cache key
     * @param array $current_data Current data
     * @param int $time_window Time window
     */
    private static function increment_counter( string $cache_key, array $current_data, int $time_window ): void {
        $current_data['count'] = intval( $current_data['count'] ?? 0 ) + 1;
        
        $remaining_time = $time_window - ( time() - intval( $current_data['first_timestamp'] ?? time() ) );
        $expiry = max( 1, $remaining_time );
        
        set_transient( $cache_key, maybe_serialize( $current_data ), $expiry );
    }
}