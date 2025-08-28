<?php
/**
 * Abstract base class for News providers
 * Ensures normalized response format
 */

namespace KS_CRM\News;

defined( 'ABSPATH' ) || exit;

abstract class Abstract_News_Provider {

    /**
     * Fetch news articles
     *
     * @param array $params Parameters like query, limit, language, etc.
     * @return array Normalized array of articles
     */
    abstract public function fetch( array $params ): array;

    /**
     * Get provider key/identifier
     *
     * @return string Provider key
     */
    abstract public function get_key(): string;

    /**
     * Get provider display name
     *
     * @return string Provider name
     */
    abstract public function get_name(): string;

    /**
     * Check if provider is enabled/available
     *
     * @return bool True if enabled
     */
    abstract public function is_enabled(): bool;

    /**
     * Get required secrets for this provider
     *
     * @return array Array of required constant names
     */
    public function get_required_secrets(): array {
        return [];
    }

    /**
     * Normalize article data to standard format
     * Ensures consistent keys: id, title, url, source, published_at
     *
     * @param array $raw_data Raw article data from provider
     * @return array Normalized article data
     */
    protected function normalize_article( array $raw_data ): array {
        return [
            'id' => $raw_data['id'] ?? $this->generate_article_id( $raw_data ),
            'title' => sanitize_text_field( $raw_data['title'] ?? '' ),
            'url' => esc_url_raw( $raw_data['url'] ?? '' ),
            'source' => sanitize_text_field( $raw_data['source'] ?? $this->get_name() ),
            'published_at' => $this->normalize_date( $raw_data['published_at'] ?? $raw_data['publishedAt'] ?? '' ),
            'description' => wp_trim_words( wp_strip_all_tags( $raw_data['description'] ?? '' ), 30 ),
            'image_url' => esc_url_raw( $raw_data['image_url'] ?? $raw_data['urlToImage'] ?? '' ),
        ];
    }

    /**
     * Generate article ID from URL hash if no ID provided
     *
     * @param array $raw_data Raw article data
     * @return string Generated ID
     */
    protected function generate_article_id( array $raw_data ): string {
        $url = $raw_data['url'] ?? '';
        return $url ? md5( $url ) : uniqid( $this->get_key() . '_' );
    }

    /**
     * Normalize date to ISO 8601 format
     *
     * @param string $date_string Date string in various formats
     * @return string ISO 8601 formatted date
     */
    protected function normalize_date( string $date_string ): string {
        if ( empty( $date_string ) ) {
            return gmdate( 'c' ); // Current time in ISO 8601
        }

        $timestamp = strtotime( $date_string );
        if ( $timestamp === false ) {
            return gmdate( 'c' ); // Fallback to current time
        }

        return gmdate( 'c', $timestamp );
    }

    /**
     * Validate required parameters
     *
     * @param array $params Input parameters
     * @param array $required Required parameter names
     * @throws \InvalidArgumentException If required params missing
     */
    protected function validate_params( array $params, array $required = [] ): void {
        foreach ( $required as $param ) {
            if ( ! isset( $params[ $param ] ) || empty( $params[ $param ] ) ) {
                throw new \InvalidArgumentException( "Missing required parameter: {$param}" );
            }
        }
    }

    /**
     * Apply default parameters
     *
     * @param array $params Input parameters
     * @return array Parameters with defaults applied
     */
    protected function apply_defaults( array $params ): array {
        return wp_parse_args( $params, [
            'limit' => 10,
            'query' => '',
            'language' => 'en',
            'sort_by' => 'publishedAt',
        ] );
    }
}