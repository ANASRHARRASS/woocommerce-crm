<?php

namespace Anas\WCCRM\News\Providers;

use Anas\WCCRM\News\DTO\Article;
use Anas\WCCRM\Security\CredentialResolver;

defined( 'ABSPATH' ) || exit;

/**
 * NewsAPI provider (stub implementation)
 * TODO: Implement actual NewsAPI integration
 */
class NewsApiProvider extends AbstractHttpProvider {

    public function fetch( array $params ): array {
        // TODO: Implement actual NewsAPI integration
        // For now, return empty array to avoid errors
        
        $api_key = $this->credentialResolver->get( 'NEWSAPI_KEY' );
        if ( empty( $api_key ) ) {
            // Skip gracefully if no API key
            return [];
        }

        // Placeholder: Would make actual API call here
        // Example implementation would be:
        /*
        $query = sanitize_text_field( $params['query'] ?? 'business' );
        $limit = min( max( 1, (int) ( $params['limit'] ?? 10 ) ), 100 );
        $language = sanitize_text_field( $params['language'] ?? 'en' );
        
        $url = add_query_arg( [
            'q' => $query,
            'pageSize' => $limit,
            'language' => $language,
            'sortBy' => 'publishedAt',
            'apiKey' => $api_key,
        ], 'https://newsapi.org/v2/everything' );
        
        $data = $this->make_request( $url );
        
        if ( ! isset( $data['articles'] ) || ! is_array( $data['articles'] ) ) {
            return [];
        }
        
        return $this->create_articles( $data['articles'] );
        */

        return []; // Return empty array for stub
    }

    public function get_key(): string {
        return 'newsapi';
    }

    public function get_name(): string {
        return 'NewsAPI';
    }

    public function is_enabled(): bool {
        // Only enabled if API key is available
        $api_key = $this->credentialResolver->get( 'NEWSAPI_KEY' );
        return ! empty( $api_key );
    }

    public function get_cache_ttl(): int {
        return 1800; // 30 minutes
    }

    public function get_rate_limit(): int {
        return 50; // NewsAPI free tier allows 1000 requests per day
    }

    protected function normalize_article_data( array $raw_article ): array {
        return [
            'title' => $raw_article['title'] ?? '',
            'url' => $raw_article['url'] ?? '',
            'source' => $raw_article['source']['name'] ?? $this->get_name(),
            'published_at' => $raw_article['publishedAt'] ?? '',
            'description' => $raw_article['description'] ?? '',
            'urlToImage' => $raw_article['urlToImage'] ?? '',
        ];
    }
}