<?php

namespace Anas\WCCRM\News\DTO;

defined( 'ABSPATH' ) || exit;

/**
 * News article data transfer object
 */
class Article {

    public string $id;
    public string $title;
    public string $url;
    public string $source;
    public string $published_at;
    public array $raw;

    public function __construct( array $data ) {
        $this->id = $this->generate_id( $data );
        $this->title = sanitize_text_field( $data['title'] ?? '' );
        $this->url = esc_url_raw( $data['url'] ?? '' );
        $this->source = sanitize_text_field( $data['source'] ?? '' );
        $this->published_at = $this->parse_date( $data['published_at'] ?? $data['publishedAt'] ?? '' );
        $this->raw = $data;
    }

    protected function generate_id( array $data ): string {
        // Generate a hash-based ID from URL or title
        $url = $data['url'] ?? '';
        $title = $data['title'] ?? '';
        
        if ( ! empty( $url ) ) {
            return md5( $url );
        }
        
        if ( ! empty( $title ) ) {
            return md5( $title . ( $data['source'] ?? '' ) );
        }
        
        return md5( wp_json_encode( $data ) );
    }

    protected function parse_date( string $date ): string {
        if ( empty( $date ) ) {
            return '';
        }

        // Try to parse various date formats
        $timestamp = strtotime( $date );
        if ( $timestamp === false ) {
            return $date; // Return original if parsing fails
        }

        return date( 'Y-m-d H:i:s', $timestamp );
    }

    public function to_array(): array {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'url' => $this->url,
            'source' => $this->source,
            'published_at' => $this->published_at,
            'raw' => $this->raw,
        ];
    }

    public function get_excerpt( int $length = 150 ): string {
        $description = $this->raw['description'] ?? $this->raw['content'] ?? '';
        
        if ( empty( $description ) ) {
            return '';
        }

        $description = strip_tags( $description );
        
        if ( strlen( $description ) <= $length ) {
            return $description;
        }

        return substr( $description, 0, $length ) . '...';
    }

    public function get_image_url(): string {
        return esc_url( $this->raw['urlToImage'] ?? $this->raw['image'] ?? '' );
    }
}