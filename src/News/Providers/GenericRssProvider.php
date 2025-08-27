<?php

namespace Anas\WCCRM\News\Providers;

use Anas\WCCRM\News\Contracts\NewsProviderInterface;
use Anas\WCCRM\News\DTO\Article;
use Anas\WCCRM\Security\CredentialResolver;

defined( 'ABSPATH' ) || exit;

/**
 * Generic RSS provider (stub implementation)
 * TODO: Implement actual RSS parsing
 */
class GenericRssProvider implements NewsProviderInterface {

    private CredentialResolver $credentialResolver;

    public function __construct( CredentialResolver $credentialResolver ) {
        $this->credentialResolver = $credentialResolver;
    }

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
        $limit = (int) ( $params['limit'] ?? 10 );
        
        // Fetch RSS feed
        $response = wp_remote_get( $rss_url, [
            'timeout' => 30,
            'user-agent' => 'WCCRM RSS Reader 1.0',
        ] );
        
        if ( is_wp_error( $response ) ) {
            throw new \Exception( 'RSS request failed: ' . $response->get_error_message() );
        }
        
        $body = wp_remote_retrieve_body( $response );
        
        // Parse XML
        libxml_use_internal_errors( true );
        $xml = simplexml_load_string( $body );
        
        if ( $xml === false ) {
            throw new \Exception( 'Failed to parse RSS XML' );
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
        return ! empty( $rss_url );
    }
}