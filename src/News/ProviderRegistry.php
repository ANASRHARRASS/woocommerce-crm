<?php

namespace Anas\WCCRM\News;

use Anas\WCCRM\News\Contracts\NewsProviderInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Registry for news providers
 */
class ProviderRegistry {

    private array $providers = [];

    /**
     * Register a news provider
     */
    public function register( string $key, NewsProviderInterface $provider ): void {
        $this->providers[ sanitize_key( $key ) ] = $provider;
    }

    /**
     * Get a provider by key
     */
    public function get( string $key ): ?NewsProviderInterface {
        return $this->providers[ sanitize_key( $key ) ] ?? null;
    }

    /**
     * Get all registered providers
     */
    public function list(): array {
        return $this->providers;
    }

    /**
     * Get enabled providers only
     */
    public function list_enabled(): array {
        return array_filter( $this->providers, function( NewsProviderInterface $provider ) {
            return $provider->is_enabled();
        } );
    }

    /**
     * Check if a provider is registered
     */
    public function has( string $key ): bool {
        return isset( $this->providers[ sanitize_key( $key ) ] );
    }

    /**
     * Unregister a provider
     */
    public function unregister( string $key ): void {
        unset( $this->providers[ sanitize_key( $key ) ] );
    }
}