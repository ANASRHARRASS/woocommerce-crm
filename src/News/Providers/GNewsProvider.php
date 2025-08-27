<?php

namespace Anas\WCCRM\News\Providers;

use Anas\WCCRM\News\DTO\Article;
use Anas\WCCRM\Security\CredentialResolver;

defined( 'ABSPATH' ) || exit;

/**
 * GNews provider (stub implementation)
 * TODO: Implement actual GNews integration
 */
class GNewsProvider extends AbstractHttpProvider {

    public function fetch( array $params ): array {
        // TODO: Implement actual GNews integration
        // For now, return empty array to avoid errors
        
        $api_key = $this->credentialResolver->get( 'GNEWS_KEY' );
        if ( empty( $api_key ) ) {
            // Skip gracefully if no API key
            return [];
        }

        // Placeholder: Would make actual API call here
        // Example implementation would be:
        /*
        $query = sanitize_text_field( $params['query'] ?? 'business' );
        $limit = min( max( 1, (int) ( $params['limit'] ?? 10 ) ), 100 );
        $lang = sanitize_text_field( $params['lang'] ?? 'en' );
        
        $url = add_query_arg( [
            'q' => $query,
            'lang' => $lang,
            'max' => $limit,
            'sortby' => 'publishedAt',
            'apikey' => $api_key,
        ], 'https://gnews.io/api/v4/search' );
        
        $data = $this->make_request( $url );
        
        if ( ! isset( $data['articles'] ) || ! is_array( $data['articles'] ) ) {
            return [];
        }
        
        return $this->create_articles( $data['articles'] );
        */

        return []; // Return empty array for stub
    }

    public function get_key(): string {
        return 'gnews';
    }

    public function get_name(): string {
        return 'GNews';
    }

    public function is_enabled(): bool {
        // Only enabled if API key is available
        $api_key = $this->credentialResolver->get( 'GNEWS_KEY' );
        return ! empty( $api_key );
    }

    public function get_cache_ttl(): int {
        return 3600; // 1 hour
    }

    public function get_rate_limit(): int {
        return 100; // GNews allows higher rate limits
    }

    protected function normalize_article_data( array $raw_article ): array {
        return [
            'title' => $raw_article['title'] ?? '',
            'url' => $raw_article['url'] ?? '',
            'source' => $raw_article['source']['name'] ?? $this->get_name(),
            'published_at' => $raw_article['publishedAt'] ?? '',
            'description' => $raw_article['description'] ?? '',
            'urlToImage' => $raw_article['image'] ?? '',
        ];
    }
}