<?php

namespace Anas\WCCRM\News\Providers;

use Anas\WCCRM\News\Contracts\NewsProviderInterface;
use Anas\WCCRM\News\DTO\Article;
use Anas\WCCRM\Security\CredentialResolver;

defined( 'ABSPATH' ) || exit;

/**
 * NewsAPI provider (stub implementation)
 * TODO: Implement actual NewsAPI integration
 */
class NewsApiProvider implements NewsProviderInterface {

    private CredentialResolver $credentialResolver;

    public function __construct( CredentialResolver $credentialResolver ) {
        $this->credentialResolver = $credentialResolver;
    }

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
        $query = $params['query'] ?? 'business';
        $limit = min( (int) ( $params['limit'] ?? 10 ), 100 );
        
        $response = wp_remote_get( "https://newsapi.org/v2/everything?q={$query}&pageSize={$limit}&apiKey={$api_key}" );
        
        if ( is_wp_error( $response ) ) {
            throw new \Exception( 'NewsAPI request failed: ' . $response->get_error_message() );
        }
        
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        
        if ( ! isset( $data['articles'] ) ) {
            return [];
        }
        
        return array_map( function( $article ) {
            return new Article( [
                'title' => $article['title'],
                'url' => $article['url'],
                'source' => $article['source']['name'] ?? 'NewsAPI',
                'published_at' => $article['publishedAt'],
                'description' => $article['description'],
                'urlToImage' => $article['urlToImage'],
            ] );
        }, $data['articles'] );
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
}