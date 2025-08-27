<?php

namespace Anas\WCCRM\News\Providers;

use Anas\WCCRM\News\Contracts\NewsProviderInterface;
use Anas\WCCRM\News\DTO\Article;
use Anas\WCCRM\Security\CredentialResolver;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract base class for HTTP-based news providers
 */
abstract class AbstractHttpProvider implements NewsProviderInterface {

    protected CredentialResolver $credentialResolver;

    public function __construct( CredentialResolver $credentialResolver ) {
        $this->credentialResolver = $credentialResolver;
    }

    /**
     * Make an HTTP request to the provider API
     */
    protected function make_request( string $url, array $args = [] ): array {
        $default_args = [
            'timeout' => 30,
            'user-agent' => 'WCCRM/1.0 (WordPress)',
            'headers' => [],
        ];
        
        $args = wp_parse_args( $args, $default_args );
        
        $response = wp_remote_get( $url, $args );
        
        if ( is_wp_error( $response ) ) {
            error_log( 'WCCRM News: HTTP request failed for ' . $this->get_key() . ': ' . $response->get_error_message() );
            return [];
        }
        
        $response_code = wp_remote_retrieve_response_code( $response );
        if ( $response_code < 200 || $response_code >= 300 ) {
            error_log( 'WCCRM News: HTTP ' . $response_code . ' error for ' . $this->get_key() );
            return [];
        }
        
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            error_log( 'WCCRM News: JSON decode error for ' . $this->get_key() . ': ' . json_last_error_msg() );
            return [];
        }
        
        return $data ?: [];
    }

    /**
     * Create Article DTOs from raw API data
     */
    protected function create_articles( array $raw_articles ): array {
        $articles = [];
        
        foreach ( $raw_articles as $raw_article ) {
            try {
                $article_data = $this->normalize_article_data( $raw_article );
                if ( ! empty( $article_data['title'] ) && ! empty( $article_data['url'] ) ) {
                    $articles[] = new Article( $article_data );
                }
            } catch ( \Exception $e ) {
                // Skip malformed articles, log error
                error_log( 'WCCRM News: Error creating article for ' . $this->get_key() . ': ' . $e->getMessage() );
            }
        }
        
        return $articles;
    }

    /**
     * Normalize raw article data to common format
     * Override in child classes for provider-specific mapping
     */
    protected function normalize_article_data( array $raw_article ): array {
        return [
            'title' => $raw_article['title'] ?? '',
            'url' => $raw_article['url'] ?? '',
            'source' => $this->get_name(),
            'published_at' => $raw_article['published_at'] ?? $raw_article['publishedAt'] ?? '',
            'description' => $raw_article['description'] ?? '',
            'urlToImage' => $raw_article['urlToImage'] ?? $raw_article['image'] ?? '',
        ];
    }

    /**
     * Get cache TTL for this provider (in seconds)
     * Override in child classes for provider-specific values
     */
    public function get_cache_ttl(): int {
        return 1800; // 30 minutes default
    }

    /**
     * Get rate limit for this provider (requests per minute)
     * Override in child classes for provider-specific limits
     */
    public function get_rate_limit(): int {
        return 60; // 60 requests per minute default
    }
}