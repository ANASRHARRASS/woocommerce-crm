<?php

namespace Anas\WCCRM\News\Providers;

use Anas\WCCRM\News\DTO\Article;
use Anas\WCCRM\Security\CredentialResolver;

defined( 'ABSPATH' ) || exit;

/**
 * Generic RSS provider (stub implementation)
 * TODO: Implement actual RSS parsing
 */
class GenericRssProvider extends AbstractHttpProvider {

    public function fetch( array $params ): array {
        // TODO: Implement actual RSS parsing
        // For now, return empty array to avoid errors
        
        $rss_url = $this->credentialResolver->get( 'RSS_FEED_URL' );
        if ( empty( $rss_url ) ) {
            // Skip gracefully if no RSS URL configured
            return [];
        }

        // Placeholder: Would parse RSS feed here
        // Example implementation would be:
        /*
        $limit = max( 1, (int) ( $params['limit'] ?? 10 ) );
        
        $response_data = $this->make_request( $rss_url, [
            'headers' => [
                'Accept' => 'application/rss+xml, application/xml, text/xml',
            ],
        ] );
        
        // For RSS, make_request would need to be modified to handle XML
        // This would require custom XML parsing logic
        
        $body = wp_remote_retrieve_body( $response );
        
        // Parse XML
        libxml_use_internal_errors( true );
        $xml = simplexml_load_string( $body );
        
        if ( $xml === false ) {
            error_log( 'WCCRM News: Failed to parse RSS XML for ' . $this->get_key() );
            return [];
        }
        
        $articles = [];
        $items = $xml->channel->item ?? $xml->item ?? [];
        
        foreach ( $items as $index => $item ) {
            if ( $index >= $limit ) {
                break;
            }
            
            $articles[] = new Article( [
                'title' => (string) $item->title,
                'url' => (string) $item->link,
                'source' => (string) ( $xml->channel->title ?? 'RSS Feed' ),
                'published_at' => (string) $item->pubDate,
                'description' => (string) $item->description,
            ] );
        }
        
        return $articles;
        */

        return []; // Return empty array for stub
    }

    public function get_key(): string {
        return 'generic_rss';
    }

    public function get_name(): string {
        return 'Generic RSS';
    }

    public function is_enabled(): bool {
        // Only enabled if RSS URL is configured
        $rss_url = $this->credentialResolver->get( 'RSS_FEED_URL' );
        return ! empty( $rss_url ) && filter_var( $rss_url, FILTER_VALIDATE_URL );
    }

    public function get_cache_ttl(): int {
        return 7200; // 2 hours for RSS feeds
    }

    public function get_rate_limit(): int {
        return 30; // Conservative rate limit for RSS feeds
    }

    protected function normalize_article_data( array $raw_article ): array {
        return [
            'title' => $raw_article['title'] ?? '',
            'url' => $raw_article['url'] ?? $raw_article['link'] ?? '',
            'source' => $raw_article['source'] ?? $this->get_name(),
            'published_at' => $raw_article['published_at'] ?? $raw_article['pubDate'] ?? '',
            'description' => $raw_article['description'] ?? '',
            'urlToImage' => $raw_article['urlToImage'] ?? '',
        ];
    }
}