<?php

namespace Anas\WCCRM\Security;

defined( 'ABSPATH' ) || exit;

/**
 * Secure credential resolver with encryption support
 * TODO: Consider rotating encryption method for enhanced security
 */
class CredentialResolver {

    private const OPTION_KEY = 'wccrm_secure_credentials';
    private const CIPHER_METHOD = 'AES-256-CBC';

    public function get( string $key ): ?string {
        $upper_key = strtoupper( $key );
        
        // 1. Check environment variable
        $env_key = 'WCCRM_' . $upper_key;
        $env_value = $_ENV[ $env_key ] ?? getenv( $env_key );
        if ( $env_value !== false && $env_value !== '' ) {
            return $env_value;
        }
        
        // 2. Check defined constant
        $const_name = 'WCCRM_' . $upper_key;
        if ( defined( $const_name ) ) {
            return constant( $const_name );
        }
        
        // 3. Check encrypted option
        return $this->get_encrypted_option( $key );
    }

    public function set( string $key, string $value ): bool {
        $encrypted_data = get_option( self::OPTION_KEY, [] );
        
        if ( ! is_array( $encrypted_data ) ) {
            $encrypted_data = [];
        }
        
        $encrypted_value = $this->encrypt( $value );
        if ( $encrypted_value === null ) {
            return false;
        }
        
        $encrypted_data[ $key ] = $encrypted_value;
        
        return update_option( self::OPTION_KEY, $encrypted_data );
    }

    public function delete( string $key ): bool {
        $encrypted_data = get_option( self::OPTION_KEY, [] );
        
        if ( ! is_array( $encrypted_data ) || ! isset( $encrypted_data[ $key ] ) ) {
            return true; // Already deleted
        }
        
        unset( $encrypted_data[ $key ] );
        
        return update_option( self::OPTION_KEY, $encrypted_data );
    }

    /**
     * Mask credential value for display (show only last 4 characters)
     */
    public function mask_value( string $value ): string {
        if ( strlen( $value ) <= 4 ) {
            return str_repeat( '*', strlen( $value ) );
        }
        
        return str_repeat( '*', strlen( $value ) - 4 ) . substr( $value, -4 );
    }

    /**
     * Get all stored credential keys (for admin UI)
     */
    public function get_stored_keys(): array {
        $encrypted_data = get_option( self::OPTION_KEY, [] );
        
        if ( ! is_array( $encrypted_data ) ) {
            return [];
        }
        
        return array_keys( $encrypted_data );
    }

    private function get_encrypted_option( string $key ): ?string {
        $encrypted_data = get_option( self::OPTION_KEY, [] );
        
        if ( ! is_array( $encrypted_data ) || ! isset( $encrypted_data[ $key ] ) ) {
            return null;
        }
        
        return $this->decrypt( $encrypted_data[ $key ] );
    }

    private function encrypt( string $data ): ?string {
        $encryption_key = $this->get_encryption_key();
        if ( ! $encryption_key ) {
            return null;
        }
        
        $iv = openssl_random_pseudo_bytes( openssl_cipher_iv_length( self::CIPHER_METHOD ) );
        $encrypted = openssl_encrypt( $data, self::CIPHER_METHOD, $encryption_key, 0, $iv );
        
        if ( $encrypted === false ) {
            return null;
        }
        
        return base64_encode( $encrypted . '::' . $iv );
    }

    private function decrypt( string $data ): ?string {
        $encryption_key = $this->get_encryption_key();
        if ( ! $encryption_key ) {
            return null;
        }
        
        $data = base64_decode( $data );
        if ( $data === false ) {
            return null;
        }
        
        $parts = explode( '::', $data, 2 );
        if ( count( $parts ) !== 2 ) {
            return null;
        }
        
        list( $encrypted_data, $iv ) = $parts;
        
        $decrypted = openssl_decrypt( $encrypted_data, self::CIPHER_METHOD, $encryption_key, 0, $iv );
        
        return $decrypted !== false ? $decrypted : null;
    }

    private function get_encryption_key(): ?string {
        // Use WordPress auth keys for encryption key derivation
        $auth_key = defined( 'AUTH_KEY' ) ? AUTH_KEY : '';
        $secure_auth_key = defined( 'SECURE_AUTH_KEY' ) ? SECURE_AUTH_KEY : '';
        
        if ( empty( $auth_key ) || empty( $secure_auth_key ) ) {
            error_log( 'WCCRM: AUTH_KEY or SECURE_AUTH_KEY not defined in wp-config.php' );
            return null;
        }
        
        // Create a 32-byte key from the auth keys
        return hash( 'sha256', $auth_key . $secure_auth_key, true );
    }
}