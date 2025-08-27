<?php

namespace Anas\WCCRM\Messaging\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * Interface for messaging channels (email, SMS, WhatsApp, etc.)
 */
interface ChannelInterface {

    /**
     * Get channel identifier
     */
    public function get_channel_id(): string;

    /**
     * Get channel display name
     */
    public function get_display_name(): string;

    /**
     * Check if channel is enabled and configured
     */
    public function is_enabled(): bool;

    /**
     * Send message through this channel
     * 
     * @param array $message Message data
     * @param array $recipient Recipient information
     * @return array Send result
     */
    public function send_message( array $message, array $recipient ): array;

    /**
     * Validate message format for this channel
     * 
     * @param array $message Message data
     * @return array Validation result
     */
    public function validate_message( array $message ): array;

    /**
     * Get supported message types for this channel
     * 
     * @return array List of supported types
     */
    public function get_supported_types(): array;

    /**
     * Get channel-specific configuration options
     * 
     * @return array Configuration schema
     */
    public function get_config_schema(): array;

    /**
     * Test channel connectivity/configuration
     * 
     * @return array Test result
     */
    public function test_connection(): array;
}