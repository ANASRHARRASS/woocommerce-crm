<?php
/**
 * Provider Registry service
 * Manages both news and shipping providers with unified interface
 */

namespace KS_CRM\Providers;

defined( 'ABSPATH' ) || exit;

class Provider_Registry {

    private array $news_providers = [];
    private array $shipping_providers = [];

    /**
     * Register a news provider
     *
     * @param string $key Provider key
     * @param object $provider Provider instance implementing news interface
     */
    public function register_news_provider( string $key, $provider ): void {
        $this->news_providers[ sanitize_key( $key ) ] = $provider;
    }

    /**
     * Register a shipping provider
     *
     * @param string $key Provider key  
     * @param object $provider Provider instance implementing shipping interface
     */
    public function register_shipping_provider( string $key, $provider ): void {
        $this->shipping_providers[ sanitize_key( $key ) ] = $provider;
    }

    /**
     * Get a news provider by key
     *
     * @param string $key Provider key
     * @return object|null Provider instance or null
     */
    public function get_news_provider( string $key ) {
        return $this->news_providers[ sanitize_key( $key ) ] ?? null;
    }

    /**
     * Get a shipping provider by key
     *
     * @param string $key Provider key
     * @return object|null Provider instance or null
     */
    public function get_shipping_provider( string $key ) {
        return $this->shipping_providers[ sanitize_key( $key ) ] ?? null;
    }

    /**
     * Get all news providers
     *
     * @return array Array of news providers
     */
    public function get_news_providers(): array {
        return $this->news_providers;
    }

    /**
     * Get all shipping providers
     *
     * @return array Array of shipping providers
     */
    public function get_shipping_providers(): array {
        return $this->shipping_providers;
    }

    /**
     * Get enabled news providers only
     *
     * @return array Array of enabled news providers
     */
    public function get_enabled_news_providers(): array {
        return array_filter( $this->news_providers, function( $provider ) {
            return method_exists( $provider, 'is_enabled' ) && $provider->is_enabled();
        });
    }

    /**
     * Get enabled shipping providers only
     *
     * @return array Array of enabled shipping providers
     */
    public function get_enabled_shipping_providers(): array {
        return array_filter( $this->shipping_providers, function( $provider ) {
            return method_exists( $provider, 'is_enabled' ) && $provider->is_enabled();
        });
    }

    /**
     * Check if a news provider is registered
     *
     * @param string $key Provider key
     * @return bool True if registered
     */
    public function has_news_provider( string $key ): bool {
        return isset( $this->news_providers[ sanitize_key( $key ) ] );
    }

    /**
     * Check if a shipping provider is registered
     *
     * @param string $key Provider key
     * @return bool True if registered
     */
    public function has_shipping_provider( string $key ): bool {
        return isset( $this->shipping_providers[ sanitize_key( $key ) ] );
    }

    /**
     * Get all required secrets for active providers
     * Used for admin notices about missing API keys
     *
     * @return array Array of required secret names
     */
    public function get_required_secrets(): array {
        $secrets = [];

        // Check news providers
        foreach ( $this->news_providers as $provider ) {
            if ( method_exists( $provider, 'get_required_secrets' ) ) {
                $secrets = array_merge( $secrets, $provider->get_required_secrets() );
            }
        }

        // Check shipping providers  
        foreach ( $this->shipping_providers as $provider ) {
            if ( method_exists( $provider, 'get_required_secrets' ) ) {
                $secrets = array_merge( $secrets, $provider->get_required_secrets() );
            }
        }

        return array_unique( $secrets );
    }
}