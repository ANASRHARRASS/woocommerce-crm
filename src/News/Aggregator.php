<?php

namespace Anas\WCCRM\News;

use Anas\WCCRM\News\DTO\Article;

defined( 'ABSPATH' ) || exit;

/**
 * News aggregator for collecting articles from multiple providers
 * TODO: Add news caching for better performance
 */
class Aggregator {

    private ProviderRegistry $providerRegistry;

    public function __construct( ProviderRegistry $providerRegistry ) {
        $this->providerRegistry = $providerRegistry;
    }

    /**
     * Fetch articles from all enabled providers
     */
    public function fetch( array $params ): array {
        $all_articles = [];
        $limit = (int) ( $params['limit'] ?? 20 );
        $enabled_providers = $this->providerRegistry->list_enabled();

        if ( empty( $enabled_providers ) ) {
            return $all_articles;
        }

        foreach ( $enabled_providers as $provider ) {
            try {
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

        return $all_articles;
    }

    /**
     * Fetch articles from specific provider
     */
    public function fetch_from_provider( string $provider_key, array $params ): array {
        $provider = $this->providerRegistry->get( $provider_key );
        
        if ( ! $provider || ! $provider->is_enabled() ) {
            return [];
        }

        try {
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
     * Get articles with caching (placeholder)
     * TODO: Implement proper caching mechanism
     */
    public function get_cached( array $params, int $cache_duration = 3600 ): array {
        $cache_key = 'wccrm_news_' . md5( wp_json_encode( $params ) );
        $cached = get_transient( $cache_key );

        if ( $cached !== false && is_array( $cached ) ) {
            return $cached;
        }

        $articles = $this->fetch( $params );
        
        // Convert articles to arrays for caching
        $cached_data = array_map( function( Article $article ) {
            return $article->to_array();
        }, $articles );

        set_transient( $cache_key, $cached_data, $cache_duration );

        return $articles;
    }
}