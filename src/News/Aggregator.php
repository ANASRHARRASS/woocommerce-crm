<?php

namespace Anas\WCCRM\News;

use Anas\WCCRM\News\DTO\Article;

defined( 'ABSPATH' ) || exit;

/**
 * News aggregator for collecting articles from multiple providers
 * with enhanced caching and rate limiting
 */
class Aggregator {

    private ProviderRegistry $providerRegistry;
    private RateLimiter $rateLimiter;

    public function __construct( ProviderRegistry $providerRegistry, RateLimiter $rateLimiter = null ) {
        $this->providerRegistry = $providerRegistry;
        $this->rateLimiter = $rateLimiter ?: new RateLimiter();
    }

    /**
     * Fetch articles from all enabled providers with caching and rate limiting
     */
    public function fetch( array $params ): array {
        $limit = (int) ( $params['limit'] ?? 20 );
        $use_cache = (bool) ( $params['use_cache'] ?? true );
        
        if ( $use_cache ) {
            $cached = $this->get_cached_articles( $params );
            if ( ! empty( $cached ) ) {
                return $cached;
            }
        }

        $all_articles = [];
        $enabled_providers = $this->providerRegistry->list_enabled();

        if ( empty( $enabled_providers ) ) {
            return $all_articles;
        }

        foreach ( $enabled_providers as $provider ) {
            // Check rate limits before making request
            if ( ! $this->rateLimiter->can_make_request( $provider->get_key(), $provider->get_rate_limit() ) ) {
                error_log( 'WCCRM News: Rate limit exceeded for provider ' . $provider->get_key() );
                continue;
            }

            try {
                // Record the request attempt
                $this->rateLimiter->record_request( $provider->get_key(), $provider->get_rate_limit() );
                
                $articles = $provider->fetch( $params );
                if ( is_array( $articles ) ) {
                    $all_articles = array_merge( $all_articles, $articles );
                }
            } catch ( \Exception $e ) {
                // Log error but continue with other providers
                error_log( 'WCCRM News: Error fetching from provider ' . $provider->get_key() . ': ' . $e->getMessage() );
            }
        }

        // Remove duplicates by URL hash
        $all_articles = $this->deduplicate_articles( $all_articles );

        // Sort by published date (newest first)
        usort( $all_articles, function( Article $a, Article $b ) {
            return strtotime( $b->published_at ) <=> strtotime( $a->published_at );
        } );

        // Apply limit
        if ( $limit > 0 && count( $all_articles ) > $limit ) {
            $all_articles = array_slice( $all_articles, 0, $limit );
        }

        // Cache results if enabled
        if ( $use_cache && ! empty( $all_articles ) ) {
            $this->cache_articles( $params, $all_articles );
        }

        return $all_articles;
    }

    /**
     * Fetch articles from specific provider with rate limiting
     */
    public function fetch_from_provider( string $provider_key, array $params ): array {
        $provider = $this->providerRegistry->get( $provider_key );
        
        if ( ! $provider || ! $provider->is_enabled() ) {
            return [];
        }

        // Check rate limits
        if ( ! $this->rateLimiter->can_make_request( $provider_key, $provider->get_rate_limit() ) ) {
            error_log( 'WCCRM News: Rate limit exceeded for provider ' . $provider_key );
            return [];
        }

        try {
            // Record the request
            $this->rateLimiter->record_request( $provider_key, $provider->get_rate_limit() );
            
            return $provider->fetch( $params );
        } catch ( \Exception $e ) {
            error_log( 'WCCRM News: Error fetching from provider ' . $provider_key . ': ' . $e->getMessage() );
            return [];
        }
    }

    /**
     * Remove duplicate articles based on URL hash
     */
    protected function deduplicate_articles( array $articles ): array {
        $seen_urls = [];
        $unique_articles = [];

        foreach ( $articles as $article ) {
            if ( ! ( $article instanceof Article ) ) {
                continue;
            }

            $url_hash = md5( $article->url );
            
            if ( ! isset( $seen_urls[ $url_hash ] ) ) {
                $seen_urls[ $url_hash ] = true;
                $unique_articles[] = $article;
            }
        }

        return $unique_articles;
    }

    /**
     * Get cached articles
     */
    protected function get_cached_articles( array $params ): array {
        $cache_key = $this->get_cache_key( $params );
        $cached = get_transient( $cache_key );

        if ( $cached !== false && is_array( $cached ) ) {
            // Convert cached data back to Article objects
            return array_map( function( $data ) {
                return new Article( $data );
            }, $cached );
        }

        return [];
    }

    /**
     * Cache articles
     */
    protected function cache_articles( array $params, array $articles ): void {
        $cache_key = $this->get_cache_key( $params );
        
        // Convert articles to arrays for caching
        $cached_data = array_map( function( Article $article ) {
            return $article->to_array();
        }, $articles );

        // Use the minimum TTL from enabled providers
        $ttl = $this->get_minimum_cache_ttl();
        
        set_transient( $cache_key, $cached_data, $ttl );
    }

    /**
     * Generate cache key for parameters
     */
    protected function get_cache_key( array $params ): string {
        // Remove cache-related params from key generation
        $cache_params = $params;
        unset( $cache_params['use_cache'] );
        
        return 'wccrm_news_' . md5( wp_json_encode( $cache_params ) );
    }

    /**
     * Get minimum cache TTL from enabled providers
     */
    protected function get_minimum_cache_ttl(): int {
        $enabled_providers = $this->providerRegistry->list_enabled();
        $min_ttl = 3600; // Default 1 hour

        foreach ( $enabled_providers as $provider ) {
            $ttl = $provider->get_cache_ttl();
            if ( $ttl < $min_ttl ) {
                $min_ttl = $ttl;
            }
        }

        return $min_ttl;
    }

    /**
     * Clear all cached articles
     */
    public function clear_cache(): void {
        global $wpdb;
        
        $prefix = $wpdb->prefix . 'wccrm_news_%';
        $wpdb->query( 
            $wpdb->prepare( 
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", 
                $prefix 
            )
        );
    }

    /**
     * Get articles with caching (backward compatibility)
     * @deprecated Use fetch() with use_cache parameter instead
     */
    public function get_cached( array $params, int $cache_duration = 3600 ): array {
        return $this->fetch( array_merge( $params, [ 'use_cache' => true ] ) );
    }
}