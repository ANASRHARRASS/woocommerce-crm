<?php

namespace Anas\WCCRM\News;

defined( 'ABSPATH' ) || exit;

/**
 * Rate limiter for news providers to prevent API rate limit violations
 */
class RateLimiter {

    private const OPTION_PREFIX = 'wccrm_news_rate_limit_';

    /**
     * Check if provider can make a request
     */
    public function can_make_request( string $provider_key, int $requests_per_minute = 60 ): bool {
        $cache_key = self::OPTION_PREFIX . sanitize_key( $provider_key );
        $current_minute = floor( time() / 60 );
        
        $rate_data = get_transient( $cache_key );
        
        if ( $rate_data === false ) {
            // No rate data exists, allow request
            return true;
        }
        
        if ( ! is_array( $rate_data ) ) {
            return true;
        }
        
        $last_minute = (int) ( $rate_data['minute'] ?? 0 );
        $request_count = (int) ( $rate_data['count'] ?? 0 );
        
        // If it's a new minute, reset the counter
        if ( $current_minute > $last_minute ) {
            return true;
        }
        
        // Check if we're under the limit
        return $request_count < $requests_per_minute;
    }

    /**
     * Record a request for rate limiting
     */
    public function record_request( string $provider_key, int $requests_per_minute = 60 ): void {
        $cache_key = self::OPTION_PREFIX . sanitize_key( $provider_key );
        $current_minute = floor( time() / 60 );
        
        $rate_data = get_transient( $cache_key );
        
        if ( $rate_data === false || ! is_array( $rate_data ) ) {
            $rate_data = [
                'minute' => $current_minute,
                'count' => 0,
            ];
        }
        
        $last_minute = (int) ( $rate_data['minute'] ?? 0 );
        
        // If it's a new minute, reset the counter
        if ( $current_minute > $last_minute ) {
            $rate_data = [
                'minute' => $current_minute,
                'count' => 1,
            ];
        } else {
            $rate_data['count'] = (int) ( $rate_data['count'] ?? 0 ) + 1;
        }
        
        // Store for the rest of the current minute
        $expires_in = 60 - ( time() % 60 );
        set_transient( $cache_key, $rate_data, $expires_in );
    }

    /**
     * Get remaining requests for current minute
     */
    public function get_remaining_requests( string $provider_key, int $requests_per_minute = 60 ): int {
        $cache_key = self::OPTION_PREFIX . sanitize_key( $provider_key );
        $current_minute = floor( time() / 60 );
        
        $rate_data = get_transient( $cache_key );
        
        if ( $rate_data === false || ! is_array( $rate_data ) ) {
            return $requests_per_minute;
        }
        
        $last_minute = (int) ( $rate_data['minute'] ?? 0 );
        $request_count = (int) ( $rate_data['count'] ?? 0 );
        
        // If it's a new minute, full quota available
        if ( $current_minute > $last_minute ) {
            return $requests_per_minute;
        }
        
        return max( 0, $requests_per_minute - $request_count );
    }

    /**
     * Clear rate limit data for a provider
     */
    public function clear_provider_limits( string $provider_key ): void {
        $cache_key = self::OPTION_PREFIX . sanitize_key( $provider_key );
        delete_transient( $cache_key );
    }

    /**
     * Clear all rate limit data
     */
    public function clear_all_limits(): void {
        global $wpdb;
        
        $prefix = $wpdb->prefix . 'wccrm_news_rate_limit_%';
        $wpdb->query( 
            $wpdb->prepare( 
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", 
                $prefix 
            )
        );
    }
}