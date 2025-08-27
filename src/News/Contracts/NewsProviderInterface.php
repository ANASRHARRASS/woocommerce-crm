<?php

namespace Anas\WCCRM\News\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * Interface for news provider implementations
 */
interface NewsProviderInterface {

    /**
     * Fetch news articles
     * 
     * @param array $params Parameters like query, limit, language, etc.
     * @return \Anas\WCCRM\News\DTO\Article[]
     */
    public function fetch( array $params ): array;

    /**
     * Get provider key/identifier
     */
    public function get_key(): string;

    /**
     * Get provider display name
     */
    public function get_name(): string;

    /**
     * Check if provider is enabled/available
     */
    public function is_enabled(): bool;

    /**
     * Get cache TTL for this provider (in seconds)
     */
    public function get_cache_ttl(): int;

    /**
     * Get rate limit for this provider (requests per minute)
     */
    public function get_rate_limit(): int;
}