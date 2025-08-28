<?php
/**
 * Secrets management helper
 * Centralizes API key and credential lookup without storing secrets
 */

namespace KS_CRM\Config;

defined( 'ABSPATH' ) || exit;

class Secrets {

    /**
     * Get a secret value by constant name
     * Order: defined constant -> environment variable -> filter hook
     *
     * @param string $const_name The constant name (e.g., 'NEWSAPI_KEY')
     * @return string|null The secret value or null if not found
     */
    public static function get( string $const_name ): ?string {
        // 1. Check if constant is defined
        if ( defined( $const_name ) ) {
            $value = constant( $const_name );
            if ( ! empty( $value ) ) {
                return $value;
            }
        }

        // 2. Check environment variable
        $env_value = getenv( $const_name );
        if ( $env_value !== false && ! empty( $env_value ) ) {
            return $env_value;
        }

        // 3. Apply filter for custom lookups
        $filtered_value = apply_filters( 'kscrm_secret_lookup', null, $const_name );
        if ( ! empty( $filtered_value ) ) {
            return $filtered_value;
        }

        return null;
    }

    /**
     * Check if a secret is available
     *
     * @param string $const_name The constant name
     * @return bool True if secret is available
     */
    public static function has( string $const_name ): bool {
        return ! empty( self::get( $const_name ) );
    }

    /**
     * Get missing secrets for active providers
     * Used for admin notices
     *
     * @param array $required_secrets Array of secret names to check
     * @return array Array of missing secret names
     */
    public static function get_missing( array $required_secrets ): array {
        $missing = [];
        
        foreach ( $required_secrets as $secret ) {
            if ( ! self::has( $secret ) ) {
                $missing[] = $secret;
            }
        }

        return $missing;
    }
}