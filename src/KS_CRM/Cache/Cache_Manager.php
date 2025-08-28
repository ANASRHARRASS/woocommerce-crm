<?php
/**
 * Cache Manager with namespace support
 * Provides centralized caching with namespace-specific TTL
 */

namespace KS_CRM\Cache;

defined( 'ABSPATH' ) || exit;

class Cache_Manager {

    /**
     * Remember a value with caching
     *
     * @param string $namespace Cache namespace (e.g., 'news', 'shipping')
     * @param string $key Cache key
     * @param int|null $ttl Time to live in seconds, null to use namespace default
     * @param callable $callback Callback to generate value if not cached
     * @return mixed Cached or generated value
     */
    public static function remember( string $namespace, string $key, ?int $ttl, callable $callback ) {
        $cache_key = self::build_cache_key( $namespace, $key );
        
        // Try to get from cache
        $cached_value = get_transient( $cache_key );
        if ( $cached_value !== false ) {
            return $cached_value;
        }

        // Generate new value
        $value = call_user_func( $callback );
        
        // Store in cache
        if ( $value !== null && $value !== false ) {
            $final_ttl = $ttl ?? self::get_namespace_ttl( $namespace );
            set_transient( $cache_key, $value, $final_ttl );
        }

        return $value;
    }

    /**
     * Clear cache for a specific namespace and key
     *
     * @param string $namespace Cache namespace
     * @param string $key Cache key
     */
    public static function forget( string $namespace, string $key ): void {
        $cache_key = self::build_cache_key( $namespace, $key );
        delete_transient( $cache_key );
    }

    /**
     * Clear all cache for a namespace
     *
     * @param string $namespace Cache namespace
     */
    public static function flush_namespace( string $namespace ): void {
        global $wpdb;
        
        $cache_prefix = self::build_cache_key( $namespace, '' );
        
        // Delete all transients for this namespace
        $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            '_transient_' . $cache_prefix . '%',
            '_transient_timeout_' . $cache_prefix . '%'
        ) );
        
        // Clear object cache if available
        if ( function_exists( 'wp_cache_flush_group' ) ) {
            wp_cache_flush_group( $namespace );
        }
    }

    /**
     * Clear all KSCRM cache
     */
    public static function flush_all(): void {
        global $wpdb;
        
        // Delete all KSCRM transients
        $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            '_transient_kscrm_%',
            '_transient_timeout_kscrm_%'
        ) );
        
        // Clear object cache if available
        if ( function_exists( 'wp_cache_flush' ) ) {
            wp_cache_flush();
        }
    }

    /**
     * Build cache key with namespace prefix
     *
     * @param string $namespace Cache namespace
     * @param string $key Cache key
     * @return string Full cache key
     */
    private static function build_cache_key( string $namespace, string $key ): string {
        return 'kscrm_' . sanitize_key( $namespace ) . '_' . sanitize_key( $key );
    }

    /**
     * Get TTL for a namespace
     *
     * @param string $namespace Cache namespace
     * @return int TTL in seconds
     */
    private static function get_namespace_ttl( string $namespace ): int {
        // Apply namespace-specific filters
        $filter_name = 'kscrm_cache_ttl_' . sanitize_key( $namespace );
        $default_ttl = defined( 'KSCRM_CACHE_DEFAULT_TTL' ) ? KSCRM_CACHE_DEFAULT_TTL : 3600;
        
        return apply_filters( $filter_name, $default_ttl );
    }
}